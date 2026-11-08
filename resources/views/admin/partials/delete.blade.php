{{-- Expects $action, optional $message --}}
<form method="post" action="{{ $action }}" class="d-inline" data-confirm="{{ $message ?? 'This item will be deleted and removed from all lists.' }}">
    @csrf @method('DELETE')
    <button class="btn btn-soft-danger btn-icon" title="Delete" aria-label="Delete"><i class="bi bi-trash3"></i></button>
</form>
