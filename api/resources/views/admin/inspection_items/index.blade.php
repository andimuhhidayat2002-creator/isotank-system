@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold">Inspection Items Management (V2)</h2>
        <p class="text-muted">Manage dynamic inspection checklist items</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
        <i class="bi bi-plus-circle"></i> Add New Item
    </button>
</div>

<div class="row mb-3">
    <div class="col-md-12">
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> <strong>Info:</strong> 
            These items will appear in the Flutter inspection form. You can drag and drop rows to reorder them.
            Inactive items will not appear in the mobile app.
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <table id="itemsTable" class="table table-hover">
            <thead>
                <tr>
                    <th width="30"><i class="bi bi-grip-vertical"></i></th>
                    <th>Order</th>
                    <th>Code</th>
                    <th>Label</th>
                    <th>Category</th>
                    <th>Input Type</th>
                    <th>Applies To</th>
                    <th>Required</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="sortable-items">
                @foreach($items as $item)
                <tr data-id="{{ $item->id }}" data-order="{{ $item->order }}">
                    <td class="drag-handle" style="cursor: move;"><i class="bi bi-grip-vertical"></i></td>
                    <td><span class="badge bg-secondary">{{ $item->order }}</span></td>
                    <td><code>{{ $item->code }}</code></td>
                    <td><strong>{{ $item->label }}</strong></td>
                    <td>
                        @if($item->category)
                            <span class="badge bg-info">{{ ucfirst($item->category) }}</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        @if($item->input_type === 'condition')
                            <span class="badge bg-primary">Condition</span>
                        @elseif($item->input_type === 'text')
                            <span class="badge bg-secondary">Text</span>
                        @elseif($item->input_type === 'number')
                            <span class="badge bg-success">Number</span>
                        @elseif($item->input_type === 'date')
                            <span class="badge bg-warning">Date</span>
                        @else
                            <span class="badge bg-dark">{{ ucfirst($item->input_type) }}</span>
                        @endif
                    </td>
                    <td>
                        @if($item->applies_to === 'both')
                            <span class="badge bg-purple">Both</span>
                        @elseif($item->applies_to === 'incoming')
                            <span class="badge bg-success">Incoming</span>
                        @else
                            <span class="badge bg-danger">Outgoing</span>
                        @endif
                    </td>
                    <td>
                        @if($item->is_required)
                            <i class="bi bi-check-circle-fill text-success"></i> Yes
                        @else
                            <i class="bi bi-x-circle text-muted"></i> No
                        @endif
                    </td>
                    <td>
                        <form action="{{ route('admin.inspection-items.toggle', $item->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-sm {{ $item->is_active ? 'btn-success' : 'btn-secondary' }}">
                                @if($item->is_active)
                                    <i class="bi bi-check-circle"></i> Active
                                @else
                                    <i class="bi bi-x-circle"></i> Inactive
                                @endif
                            </button>
                        </form>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editItemModal{{ $item->id }}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-outline-danger" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#deleteItemModal{{ $item->id }}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                <!-- Edit Modal -->
                <div class="modal fade" id="editItemModal{{ $item->id }}" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <form action="{{ route('admin.inspection-items.update', $item->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Inspection Item</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Code <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="code" value="{{ $item->code }}" required>
                                            <small class="text-muted">Unique identifier (lowercase, no spaces)</small>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Label <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="label" value="{{ $item->label }}" required>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Category</label>
                                            <select class="form-select" name="category">
                                                <option value="">-- No Category --</option>
                                                @foreach($categories as $key => $label)
                                                    <option value="{{ $key }}" @if($item->category === $key) selected @endif>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Input Type <span class="text-danger">*</span></label>
                                            <select class="form-select" name="input_type" required>
                                                @foreach($inputTypes as $key => $label)
                                                    <option value="{{ $key }}" @if($item->input_type === $key) selected @endif>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Applies To <span class="text-danger">*</span></label>
                                            <select class="form-select" name="applies_to" required>
                                                <option value="both" @if($item->applies_to === 'both') selected @endif>Both</option>
                                                <option value="incoming" @if($item->applies_to === 'incoming') selected @endif>Incoming Only</option>
                                                <option value="outgoing" @if($item->applies_to === 'outgoing') selected @endif>Outgoing Only</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label d-block">Tank Type Applicability <span class="text-danger">*</span></label>
                                            @php 
                                                 // Ensure it's an array if it's null or string 
                                                 $appCats = $item->applicable_categories;
                                                 if(is_string($appCats)) $appCats = json_decode($appCats, true);
                                                 if(!is_array($appCats)) $appCats = [];
                                            @endphp
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" name="applicable_categories[]" value="T75" id="edit_t75_{{$item->id}}" @if(in_array('T75', $appCats)) checked @endif>
                                                <label class="form-check-label" for="edit_t75_{{$item->id}}">T75</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" name="applicable_categories[]" value="T11" id="edit_t11_{{$item->id}}" @if(in_array('T11', $appCats)) checked @endif>
                                                <label class="form-check-label" for="edit_t11_{{$item->id}}">T11</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" name="applicable_categories[]" value="T50" id="edit_t50_{{$item->id}}" @if(in_array('T50', $appCats)) checked @endif>
                                                <label class="form-check-label" for="edit_t50_{{$item->id}}">T50</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Display Order <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" name="order" value="{{ $item->order }}" required min="0">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label d-block">Options</label>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" name="is_required" value="1" @if($item->is_required) checked @endif>
                                                <label class="form-check-label">Required</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" name="is_active" value="1" @if($item->is_active) checked @endif>
                                                <label class="form-check-label">Active</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="description" rows="2">{{ $item->description }}</textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Update Item</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Delete Modal -->
                <div class="modal fade" id="deleteItemModal{{ $item->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="{{ route('admin.inspection-items.destroy', $item->id) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title">Delete Inspection Item</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Are you sure you want to delete <strong>{{ $item->label }}</strong>?</p>
                                    <p class="text-danger"><i class="bi bi-exclamation-triangle"></i> This action cannot be undone!</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-danger">Delete Item</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.inspection-items.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add New Inspection Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="code" required>
                            <small class="text-muted">Unique identifier (lowercase, underscore for spaces)</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Label <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="label" required>
                            <small class="text-muted">Display name shown in app</small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category">
                                <option value="">-- No Category --</option>
                                @foreach($categories as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Input Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="input_type" required>
                                @foreach($inputTypes as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Applies To <span class="text-danger">*</span></label>
                            <select class="form-select" name="applies_to" required>
                                <option value="both" selected>Both</option>
                                <option value="incoming">Incoming Only</option>
                                <option value="outgoing">Outgoing Only</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label d-block">Tank Type Applicability <span class="text-danger">*</span></label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="applicable_categories[]" value="T75" id="add_t75" checked>
                                <label class="form-check-label" for="add_t75">T75</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="applicable_categories[]" value="T11" id="add_t11">
                                <label class="form-check-label" for="add_t11">T11</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="applicable_categories[]" value="T50" id="add_t50">
                                <label class="form-check-label" for="add_t50">T50</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Display Order <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="order" value="{{ $items->max('order') + 1 }}" required min="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label d-block">Options</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_required" value="1">
                                <label class="form-check-label">Required</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                                <label class="form-check-label">Active</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="2" placeholder="Optional description for this inspection item"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Item</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#itemsTable').DataTable({
        order: [[1, 'asc']], // Order by order column
        pageLength: 50,
        columnDefs: [
            { orderable: false, targets: [0, 9] } // Disable sorting on drag handle and actions
        ]
    });

    // Initialize Sortable for drag & drop
    var el = document.getElementById('sortable-items');
    var sortable = Sortable.create(el, {
        handle: '.drag-handle',
        animation: 150,
        onEnd: function (evt) {
            // Update order numbers
            var items = [];
            $('#sortable-items tr').each(function(index) {
                var id = $(this).data('id');
                items.push({
                    id: id,
                    order: index
                });
                $(this).find('td:eq(1) .badge').text(index);
            });

            // Send AJAX request to update order
            $.ajax({
                url: '{{ route("admin.inspection-items.reorder") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    items: items
                },
                success: function(response) {
                    // Show success message
                    console.log('Order updated successfully');
                },
                error: function(xhr) {
                    alert('Failed to update order. Please refresh the page.');
                }
            });
        }
    });

    // Dynamic Category Filtering based on Tank Type
    function updateCategoryOptions() {
        // Get all checked tank types
        var checkedTypes = [];
        $('input[name="applicable_categories[]"]:checked').each(function() {
            checkedTypes.push($(this).val());
        });

        // Define which categories are T75-only
        var t75OnlyCategories = ['d', 'e', 'f', 'g']; // D, E, F, G are T75 only

        // Get all category selects (both Add and Edit modals)
        $('select[name="category"]').each(function() {
            var $select = $(this);
            var currentValue = $select.val();

            // Show/hide options based on tank types
            $select.find('option').each(function() {
                var optionValue = $(this).val();
                
                // If this is a T75-only category
                if (t75OnlyCategories.includes(optionValue)) {
                    // Only show if T75 is checked
                    if (checkedTypes.includes('T75')) {
                        $(this).show().prop('disabled', false);
                    } else {
                        $(this).hide().prop('disabled', true);
                        // If this was selected, clear it
                        if (currentValue === optionValue) {
                            $select.val('');
                        }
                    }
                } else {
                    // B, C, and "No Category" are always available
                    $(this).show().prop('disabled', false);
                }
            });
        });
    }

    // Run on checkbox change
    $('input[name="applicable_categories[]"]').on('change', function() {
        updateCategoryOptions();
    });

    // Run on modal show
    $('#addItemModal, #editItemModal').on('show.bs.modal', function() {
        setTimeout(updateCategoryOptions, 100);
    });

    // Initial run
    updateCategoryOptions();
});
</script>
<style>
.badge.bg-purple {
    background-color: #6f42c1 !important;
}
.sortable-ghost {
    opacity: 0.4;
    background-color: #f8f9fa;
}
</style>
@endpush
