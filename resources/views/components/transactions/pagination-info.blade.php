@props([
    'collection'
])

<div class="d-flex justify-content-between align-items-center">
    <div class="small text-muted">
        {{ __('Showing') }}
        <strong>{{ $collection->firstItem() }}</strong>
        {{ __('to') }}
        <strong>{{ $collection->lastItem() }}</strong>
        {{ __('of') }}
        <strong>{{ $collection->total() }}</strong>
        {{ __('results') }}
    </div>
    <div>
        {{ $collection->links('pagination::bootstrap-5') }}
    </div>
</div>