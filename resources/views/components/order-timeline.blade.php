@props(['order'])

@php
    $statuses = [
        'pending' => ['label' => 'Pending', 'icon' => 'clock', 'color' => 'warning'],
        'shipped' => ['label' => 'Shipped', 'icon' => 'truck', 'color' => 'primary'],
        'delivered' => ['label' => 'Delivered', 'icon' => 'check-circle', 'color' => 'success'],
        'cancelled' => ['label' => 'Cancelled', 'icon' => 'times-circle', 'color' => 'danger'],
    ];
    
    $currentStatus = $order->order_status ?? 'pending';
    $statusOrder = ['pending', 'shipped', 'delivered'];
    
    // For cancelled orders, show only cancelled status
    if ($currentStatus === 'cancelled') {
        $statusOrder = ['cancelled'];
    }
@endphp

<div class="order-timeline">
    <div class="timeline-container">
        @foreach($statusOrder as $index => $status)
            @php
                $statusInfo = $statuses[$status] ?? ['label' => ucfirst($status), 'icon' => 'circle', 'color' => 'secondary'];
                $isActive = $status === $currentStatus;
                $isCompleted = in_array($status, ['pending', 'shipped']) && 
                              (($status === 'pending' && in_array($currentStatus, ['pending', 'shipped', 'delivered'])) ||
                               ($status === 'shipped' && in_array($currentStatus, ['shipped', 'delivered'])));
                $isCurrent = $isActive && $currentStatus !== 'cancelled';
            @endphp
            <div class="timeline-item {{ $isActive ? 'active' : '' }} {{ $isCompleted ? 'completed' : '' }}">
                <div class="timeline-icon bg-{{ $statusInfo['color'] }}">
                    <i class="fas fa-{{ $statusInfo['icon'] }}"></i>
                </div>
                <div class="timeline-content">
                    <h6 class="mb-0">{{ $statusInfo['label'] }}</h6>
                    @if($isCurrent && $order->updated_at)
                        <small class="text-muted">{{ $order->updated_at->format('M j, Y g:i A') }}</small>
                    @endif
                </div>
                @if($index < count($statusOrder) - 1)
                    <div class="timeline-line {{ $isCompleted ? 'completed' : '' }}"></div>
                @endif
            </div>
        @endforeach
        
        @if($currentStatus === 'cancelled')
            <div class="timeline-item active">
                <div class="timeline-icon bg-danger">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="timeline-content">
                    <h6 class="mb-0">Cancelled</h6>
                    @if($order->updated_at)
                        <small class="text-muted">{{ $order->updated_at->format('M j, Y g:i A') }}</small>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

<style>
.order-timeline {
    padding: 1.5rem 0;
}
.timeline-container {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    max-width: 600px;
    margin: 0 auto;
}
.timeline-item {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
}
.timeline-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    margin-bottom: 0.5rem;
    border: 3px solid #e9ecef;
    transition: all 0.3s ease;
    z-index: 2;
    background-color: #e9ecef !important;
}
.timeline-item.active .timeline-icon,
.timeline-item.completed .timeline-icon {
    border-color: var(--bs-primary);
    background-color: var(--bs-primary) !important;
}
.timeline-item.active .timeline-icon.bg-warning,
.timeline-item.completed .timeline-icon.bg-warning {
    background-color: #ffc107 !important;
    border-color: #ffc107;
}
.timeline-item.active .timeline-icon.bg-success,
.timeline-item.completed .timeline-icon.bg-success {
    background-color: #198754 !important;
    border-color: #198754;
}
.timeline-item.active .timeline-icon.bg-danger {
    background-color: #dc3545 !important;
    border-color: #dc3545;
}
.timeline-content {
    text-align: center;
}
.timeline-content h6 {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.25rem;
}
.timeline-item.active .timeline-content h6 {
    color: var(--bs-primary);
    font-weight: 700;
}
.timeline-line {
    position: absolute;
    top: 24px;
    left: calc(50% + 24px);
    width: calc(100% - 48px);
    height: 2px;
    background-color: #e9ecef;
    z-index: 1;
}
.timeline-line.completed {
    background-color: var(--bs-primary);
}
</style>

