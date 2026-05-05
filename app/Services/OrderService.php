<?php 

namespace App\Services;

use App\Exceptions\UnAuthorizedAccessEception;
use App\Exceptions\ValidationException;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\User;
use Carbon\Carbon;
use Psr\Http\Message\ServerRequestInterface;

class OrderService
{
    
    /**
     * User orders - return all orders for the logged-in user
     */
    public function userOrders()
    {
        $user = User::find($_SESSION['user_id']);

        $orders = $user->orders;
        return  [
            'orders' => $orders,
            'status_labels' => $this->getStatusLabels()
        ];
    }

    /**
     * return single order details for user
     */
    public function showOrder(ServerRequestInterface $request, int $orderId)
    {
        $user = User::find($_SESSION['user_id']);
        $orderId = $orderId?? null;

        # Find order with items (assuming OrderItem relationship)
        $order = Order::with(['items.product', 'items'])
            ->where('id', $orderId)
            ->where(function ($query) use ($user) {
                $query->where('customer_phone', $user->phone)
                    ->orWhere('customer_email', $user->email);
            })
            ->first();

        if (!$order) {
            $_SESSION['error'] = "Order ".$orderId. " not found";
            // return $response->withHeader('Location', '/admin/orders')->withStatus(302);
        }

        # Calculate estimated delivery date
        $estimatedDelivery = $this->calculateEstimatedDelivery($order);

        return [
            'order' => $order,
            'estimated_delivery' => $estimatedDelivery,
            'status_labels' => $this->getStatusLabels(),
            'payment_status_labels' => $this->getPaymentStatusLabels()
        ];
    }

    /**
     * Admin - Show all orders with filters
     */
    public function allOrdersAdmin(ServerRequestInterface $request)
    {
        $user = User::find($_SESSION['user_id']);

        # Check if user is admin (you should have an isAdmin() method or role check)
        if (!$user || !$user->role == 'admin' ||  !$user->role == 'super_admin') {
            
        }


        # $query = Order::with(['items', 'assignedStaff', 'dispatchedRider']);
        $query = Order::with(['items']);

        # Apply filters
        $filters = $request->getQueryParams();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $query->whereBetween('created_at', [
                $filters['date_from'] . ' 00:00:00',
                $filters['date_to'] . ' 23:59:59'
            ]);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(50);

        # Statistics
        $stats = [
            'total' => Order::count(),
            'pending' => Order::where('status', 'pending')->count(),
            'processing' => Order::where('status', 'processing')->count(),
            'ready' => Order::where('status', 'ready')->count(),
            'dispatched' => Order::where('status', 'dispatched')->count(),
            'delivered' => Order::where('status', 'delivered')->count(),
            'cancelled' => Order::where('status', 'cancelled')->count(),
        ];

        return [
            'orders' => $orders,
            'stats' => $stats,
            'filters' => $filters,
            'status_labels' => $this->getStatusLabels(),
            'payment_status_labels' => $this->getPaymentStatusLabels()
        ];
    }

    /**
     * Staff - Show orders assigned to staff for processing
     */
    public function staffOrders(ServerRequestInterface $request)
    {
        $user = User::find($_SESSION['user_id']);

        # Check if user is staff
        if (!$user || (!$user->isStaff() && !$user->isAdmin())) {
            
        }


        # Orders assigned to this staff member OR unassigned orders
        $orders = Order::with('items')
            ->whereIn('status', ['pending', 'processing', 'ready'])
            ->where(function ($query) use ($user) {
                $query->where('assigned_staff_id', $user->id)
                    ->orWhereNull('assigned_staff_id');
            })
            ->orderByRaw("CASE status
                                    WHEN 'processing' THEN 1
                                    WHEN'ready' THEN 2
                                    WHEN 'pending' THEN 3
                                    ELSE 4
                                END")
            ->orderBy('created_at', 'asc')
            ->paginate(20);

        return  [
            'orders' => $orders,
            'status_labels' => $this->getStatusLabels()
        ];
    }

    /**
     * Staff - Show orders dispatched by staff after processing
     */
    public function dispatchedOrders(ServerRequestInterface $request)
    {
        $user = User::find($_SESSION['user_id']);

        # Check if user is staff
        if (!$user || (!$user->isStaff() && !$user->isAdmin())) {
            $_SESSION['error'] = 'Kindly login to continue';
            
        }


        # Orders assigned to this staff member OR unassigned orders
        $orders = Order::with('items')
            ->whereIn('status', ['completed', 'dispatched'])
            ->where('assigned_staff_id', $user->id)
            ->orWhereNull('assigned_staff_id')
            ->orderByRaw("CASE status
                                    WHEN 'processing' THEN 1
                                    WHEN'ready' THEN 2
                                    WHEN 'pending' THEN 3
                                    ELSE 4
                                END")
            ->orderBy('created_at', 'asc')
            ->paginate(20);

        return [
            'orders' => $orders,
            'status_labels' => $this->getStatusLabels()
        ];
    }

    /**
     * Assign order to staff for processing
     */
    public function assignOrder(ServerRequestInterface $request, $args)
    {
        $user = User::find($_SESSION['user_id']);
        $orderId = $args['id'] ?? null;

        if (!$user || (!$user->isStaff() && !$user->isAdmin())) {
            return throw new ValidationException("", ['error' => 'Unauthorized'], 401);
        }

        $order = Order::find($orderId);

        if (!$order) {
            return throw new ValidationException("", ['error' => 'Order not found'], 404);
        }

        # Check if order can be assigned (pending or processing status)
        if (!in_array($order->status, ['pending', 'processing'])) {
            return throw new ValidationException("", [
                'error' => 'Order cannot be assigned in its current status'
            ], 400);
        }

        $data = $request->getParsedBody();
        $staffId = $data['staff_id'] ?? $user->id;

        # Update order assignment
        $order->assigned_staff_id = $staffId;
        $order->status = 'processing';
        $order->processing_started_at = Carbon::now();
        $order->save();

        # Add to order history
        $this->addOrderHistory($order->id, 'assigned_to_staff', [
            'staff_id' => $staffId,
            'staff_name' => $user->name,
            'assigned_by' => $user->id
        ]);

        return throw new ValidationException("", [
            'success' => true,
            'message' => 'Order assigned successfully',
            'order' => $order
        ]);
    }

    /**
     * Staff - Process order (update status, add notes, etc.)
     */
    public function processOrder(ServerRequestInterface $request, $args)
    {
        $user = User::find($_SESSION['user_id']);
        $orderId = $args['id'] ?? null;

        if (!$user || (!$user->isStaff() && !$user->isAdmin())) {
            
        }

        $order = Order::with(['items.product', 'user'])->find($orderId);

        if (!$order) {
            throw new ValidationException("",['error'=>'Order not found']);
        }

        # move order status to processing & assign current user
        if ($order->status == 'pending') {
            $order->status = 'processing';
            $order->assigned_staff_id = $_SESSION['user_id'];
            $order->save();
        }
        if ($request->getMethod() === 'POST') {
            $data = $request->getParsedBody();

            # Update order status
            if (!empty($data['status'])) {
                $oldStatus = $order->status;
                $order->status = $data['status'];

                # Set timestamps based on status
                switch ($data['status']) {
                    case 'ready':
                        $order->processed_at = Carbon::now();
                        break;
                    case 'dispatched':
                        $order->dispatched_at = Carbon::now();
                        break;
                    case 'delivered':
                        $order->delivered_at = Carbon::now();
                        break;
                }

                $this->addOrderHistory($order->id, 'status_changed', [
                    'from' => $oldStatus,
                    'to' => $data['status'],
                    'changed_by' => $user->id,
                    'notes' => $data['notes'] ?? null
                ]);
            }

            # Update order notes
            if (!empty($data['internal_notes'])) {
                $order->internal_notes = $data['internal_notes'];
                $this->addOrderHistory($order->id, 'internal_notes_updated', [
                    'updated_by' => $user->id
                ]);
            }

            # Update packaging details
            if (!empty($data['package_weight']) || !empty($data['package_dimensions'])) {
                $order->package_weight = $data['package_weight'] ?? $order->package_weight;
                $order->package_dimensions = $data['package_dimensions'] ?? $order->package_dimensions;
                $order->packaging_notes = $data['packaging_notes'] ?? $order->packaging_notes;
            }

            $order->save();

            # Redirect back with success message
            // return $response
            //     ->withHeader('Location', '/staff/orders/' . $orderId . '/process')
            //     ->withStatus(302);
        }

        # Get available staff for reassignment
        $availableStaff = User::where('role', 'staff')
            ->orWhere('role', 'admin')
            ->get();

        return [
            'order' => $order,
            'available_staff' => $availableStaff,
            'status_labels' => $this->getStatusLabels(),
            'next_status_options' => $this->getNextStatusOptions($order->status)
        ];
    }

    /**
     * Staff/Dispatch - Mark order as ready for dispatch
     */
    public function markReadyForDispatch(ServerRequestInterface $request, $args)
    {
        $user = User::find($_SESSION['user_id']);
        $orderId = $args['id'] ?? null;

        if (!$user || (!$user->isStaff() && !$user->isAdmin())) {
            throw new ValidationException("", ['error' => 'Unauthorized'], 401);
        }

        $order = Order::find($orderId);

        if (!$order) {
            throw new ValidationException("", ['error' => 'Order not found'], 404);
        }

        # Verify order is in processing state
        if ($order->status !== 'processing') {
            throw new ValidationException("",[
                'error' => 'Order must be in processing status'
            ], 400);
        }

        $order->status = 'ready';
        $order->processed_at = Carbon::now();
        $order->save();

        $this->addOrderHistory($order->id, 'ready', [
            'marked_by' => $user->id,
            'timestamp' => Carbon::now()
        ]);

        return [
            'success' => true,
            'message' => 'Order marked as ready for dispatch'
        ];
    }

    /**
     * Dispatch - Show orders ready for dispatch
     */
    public function dispatchableOrders(ServerRequestInterface $request)
    {
        $user = User::find($_SESSION['user_id']);

        # Check if user is dispatch staff or admin
        if (!$user || (!$user->isDispatch() && !$user->isAdmin())) {
            throw new UnAuthorizedAccessEception();
        }

        $orders = Order::with(['items', 'assignedStaff'])
            ->whereIn('status', ['ready_for_dispatch', 'dispatched'])
            ->orderByRaw("CASE status
                                    WHEN'ready' THEN 1
                                    WHEN 'dispatched' THEN 3
                                    ELSE 4
                                END")
            ->orderBy('processed_at', 'asc')
            ->paginate(20);

        # Available riders/delivery personnel
        $riders = User::where('role', 'rider')->get();

        return  [
            'orders' => $orders,
            'riders' => $riders,
            'status_labels' => $this->getStatusLabels()
        ];
    }

    /**
     * Pending Orders - Show orders ready for dispatch
     */
    public function pendingOrders(ServerRequestInterface $request)
    {
        $user = User::find($_SESSION['user_id']);

        # Check if user is dispatch staff or admin
        if (!$user || (!$user->isDispatch() && !$user->isAdmin())) {
            throw new UnAuthorizedAccessEception();
        }

        $orders = Order::with(['items', 'assignedStaff'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return [
            'orders' => $orders,
            'status_labels' => $this->getStatusLabels()
        ];
    }

    /**
     * Pending Orders - Show orders ready for dispatch
     */
    public function completedOrders(ServerRequestInterface $request)
    {
        $user = User::find($_SESSION['user_id']);

        # Check if user is dispatch staff or admin
        if (!$user || (!$user->isStaff() && !$user->isAdmin())) {
            
             throw new UnAuthorizedAccessEception("", );
        }

        $orders = Order::with(['items', 'assignedStaff'])
            ->whereIn('status', ['delivered', 'completed'])
            ->orderBy('processed_at', 'desc')
            ->paginate(20);

        return [
            'orders' => $orders,
            'status_labels' => $this->getStatusLabels()
        ];
    }

    /**
     * Pending Orders - Show orders ready for dispatch
     */
    public function ordersInProcessing(ServerRequestInterface $request)
    {
        $user = User::find($_SESSION['user_id']);

        # Check if user is dispatch staff or admin
        if (!$user || (!$user->isStaff() && !$user->isAdmin())) {
            
             throw new ValidationException("", ['error'=>'No orders found']);
        }


        $orders = Order::with(['items', 'assignedStaff'])
            ->whereIn('status', ['delivered', 'completed'])
            ->orderBy('processed_at', 'desc')
            ->paginate(20);

        return  [
            'orders' => $orders,
            'status_labels' => $this->getStatusLabels()
        ];
    }


    /**
     * Admin - Update order status
     */
    public function updateOrderStatus(ServerRequestInterface $request, $args)
    {
        $user = User::find($_SESSION['user_id']);

        $orderId = $args['id'] ?? null;
        $data = $request->getParsedBody();

        if (empty($data['status'])) {
            throw new ValidationException("", ['error' => 'Status is required'], );
        }

        $order = Order::find($orderId);

        if (!$order) {
            throw new ValidationException("",  ['error' => 'Order not found'], 404);
        }

        $oldStatus = $order->status;
        $order->status = $data['status'];

        # Set appropriate timestamps
        $this->updateOrderTimestamps($order, $data['status']);

        $order->save();

        $this->addOrderHistory($order->id, 'admin_status_update', [
            'from' => $oldStatus,
            'to' => $data['status'],
            'admin_id' => $user->id,
            'reason' => $data['reason'] ?? null
        ]);

        return [
            'success' => true,
            'message' => 'Order status updated successfully'
        ];
    }

    /**
     * View order history/timeline
     */
    public function orderHistory(ServerRequestInterface $request, $args)
    {
        $user = User::find($_SESSION['user_id']);
        $orderId = $args['id'] ?? null;

        if (!$user) {
            
        }

        # Check if user has permission to view this order
        $order = Order::find($orderId);

        if (!$order) {
             throw new ValidationException("", ['error'=>'Order not found']);
        }

        # Authorization check (user owns order or is staff/admin)
        $canView = $this->canViewOrder($user, $order);

        if (!$canView) {
             throw new UnAuthorizedAccessEception("",);
        }

        # Get order history from database (you'll need to create an OrderHistory model)
        $history = $this->getOrderHistory($orderId);
        
        return [
            'order' => $order,
            'history' => $history,
            'status_labels' => $this->getStatusLabels()
        ];
    }

    # TODO: create method to handle COD orders display()'/orders/cod/{orderId})

    /**
     * Get formatted order history for display
     */
    private function getOrderHistory($orderId): array
    {
        $history = OrderHistory::where('order_id', $orderId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($record) {
                return [
                    'action' => $record->action,
                    'description' => $record->description,
                    'created_at' => $record->created_at,
                    'metadata' => $record->metadata,
                    'icon' => $this->getHistoryIcon($record->action),
                    'notes' => $this->getHistoryNotes($record->metadata)
                ];
            })
            ->toArray();

        return $history;
    }

    /**
     * Get icon for history action
     */
    private function getHistoryIcon(string $action): string
    {
        $icons = [
            'order_created' => 'clock',
            'status_changed' => 'refresh',
            'payment_received' => 'credit-card',
            'assigned_to_staff' => 'user-check',
            'ready' => 'package',
            'dispatched' => 'truck',
            'delivered' => 'check-circle',
            'customer_notified' => 'bell'
        ];

        return $icons[$action] ?? 'file-text';
    }

    /**
     * Extract notes from metadata
     */
    private function getHistoryNotes(array $metadata): string
    {
        $notes = [];

        if (!empty($metadata['notes'])) {
            $notes[] = $metadata['notes'];
        }

        if (!empty($metadata['reason'])) {
            $notes[] = "Reason: " . $metadata['reason'];
        }

        if (!empty($metadata['staff_name'])) {
            $notes[] = "By: " . $metadata['staff_name'];
        }

        return implode(' • ', $notes);
    }

    /**
     * Helper Methods
     */

    private function getStatusLabels(): array
    {
        return [
            'pending' => ['label' => 'Pending', 'color' => 'warning', 'icon' => 'clock'],
            'processing' => ['label' => 'Processing', 'color' => 'info', 'icon' => 'cog'],
            'ready' => ['label' => 'Ready for Dispatch', 'color' => 'primary', 'icon' => 'package'],
            'dispatched' => ['label' => 'Dispatched', 'color' => 'secondary', 'icon' => 'truck'],
            'out_for_delivery' => ['label' => 'Out for Delivery', 'color' => 'success', 'icon' => 'motorcycle'],
            'delivered' => ['label' => 'Delivered', 'color' => 'success', 'icon' => 'check-circle'],
            'cancelled' => ['label' => 'Cancelled', 'color' => 'danger', 'icon' => 'x-circle'],
            'failed_delivery' => ['label' => 'Delivery Failed', 'color' => 'danger', 'icon' => 'exclamation-triangle'],
            'returned' => ['label' => 'Returned', 'color' => 'warning', 'icon' => 'undo']
        ];
    }

    private function getPaymentStatusLabels(): array
    {
        return [
            'pending' => ['label' => 'Pending', 'color' => 'warning'],
            'processing' => ['label' => 'Processing', 'color' => 'info'],
            'paid' => ['label' => 'Paid', 'color' => 'success'],
            'failed' => ['label' => 'Failed', 'color' => 'danger'],
            'refunded' => ['label' => 'Refunded', 'color' => 'secondary'],
            'partially_refunded' => ['label' => 'Partially Refunded', 'color' => 'warning']
        ];
    }

    private function getNextStatusOptions(string $currentStatus): array
    {
        $workflow = [
            'pending' => ['processing', 'cancelled'],
            'processing' => ['ready_for_dispatch', 'cancelled'],
            'ready' => ['dispatched', 'cancelled'],
            'dispatched' => ['out_for_delivery', 'failed_delivery'],
            'out_for_delivery' => ['delivered', 'failed_delivery'],
            'failed_delivery' => ['dispatched', 'cancelled'],
            'delivered' => ['returned'],
            'cancelled' => [] # Terminal state
        ];

        return $workflow[$currentStatus] ?? [];
    }

    private function calculateEstimatedDelivery(Order $order): string
    {
        if ($order->delivered_at) {
            return 'Delivered on ' . $order->delivered_at->format('M d, Y');
        }

        if ($order->estimated_delivery_time) {
            return 'Estimated delivery: ' . $order->estimated_delivery_time->format('M d, Y H:i');
        }

        # Default estimation based on order date
        $orderDate = $order->created_at;
        $estimatedDate = $orderDate->copy()->addDays(3); # 3 business days default

        return 'Estimated delivery: ' . $estimatedDate->format('M d, Y');
    }

    private function generateTrackingNumber(): string
    {
        return 'TRK' . strtoupper(substr(md5(uniqid()), 0, 10)) . date('Ymd');
    }

    private function addOrderHistory($orderId, $action, $data = []): void
    {
        # Implement order history logging
        # You should create an OrderHistory model
        /*
        OrderHistory::create([
            'order_id' => $orderId,
            'action' => $action,
            'description' => $this->getHistoryDescription($action, $data),
            'metadata' => json_encode($data),
            'created_at' => Carbon::now()
        ]);
        */
    }

    private function updateOrderTimestamps(Order $order, string $status): void
    {
        $timestamps = [
            'processing' => 'processing_started_at',
            'ready' => 'processed_at',
            'dispatched' => 'dispatched_at',
            'out_for_delivery' => 'out_for_delivery_at',
            'delivered' => 'delivered_at',
            'cancelled' => 'cancelled_at'
        ];

        if (isset($timestamps[$status]) && !$order->{$timestamps[$status]}) {
            $order->{$timestamps[$status]} = Carbon::now();
        }
    }

    private function canViewOrder($user, Order $order): bool
    {
        # User owns the order
        if (
            $order->customer_phone === $user->phone ||
            $order->customer_email === $user->email
        ) {
            return true;
        }

        # User is staff/admin assigned to order
        if (($user->isStaff() || $user->isAdmin()) &&
            $order->assigned_staff_id === $user->id
        ) {
            return true;
        }

        # User is rider assigned to order
        if ($user->isRider() && $order->rider_id === $user->id) {
            return true;
        }

        # User is admin
        return $user->isAdmin();
    }

    private function notifyRider($riderId, Order $order): void
    {
        # Implement rider notification (email, push notification, SMS)
        # Example:
        # $rider = User::find($riderId);
        # sendSMS($rider->phone, "New delivery assigned: {$order->order_number}");
    }

    private function sendDispatchSMS(Order $order): void
    {
        # Implement SMS to customer
        # Example:
        # $message = "Your order {$order->order_number} has been dispatched. Tracking: {$order->tracking_number}";
        # sendSMS($order->customer_phone, $message);
    }

    private function sendDeliveryConfirmation(Order $order): void
    {
        # Implement delivery confirmation
        # Example:
        # $message = "Your order {$order->order_number} has been delivered. Thank you!";
        # sendSMS($order->customer_phone, $message);
    }

    /**
     * Lookup order by order number and email for public tracking
     */
    public function lookupOrder(string $orderNumber, string $email): ?array
    {
        $order = Order::with(['items.product', 'user'])
            ->where('order_number', $orderNumber)
            ->where('customer_email', $email)
            ->first();

        return $order ? $order->toArray() : null;
    }

}