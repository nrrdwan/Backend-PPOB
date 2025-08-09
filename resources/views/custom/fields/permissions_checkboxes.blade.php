{{-- Custom Permissions Checkboxes Field --}}
@php
    $field['default'] = $field['default'] ?? old($field['name']) ?? '';
    
    // Get currently selected permissions for update
    $selected_permissions = [];
    if (isset($entry) && $entry->exists) {
        $selected_permissions = $entry->permissions->pluck('id')->toArray();
    } elseif (old($field['name'])) {
        $selected_permissions = old($field['name']);
    }
@endphp

<div class="form-group col-sm-12" element="div" bp-field-wrapper="true" bp-field-name="{{ $field['name'] }}" bp-field-type="{{ $field['type'] }}">
    <label>{!! $field['label'] !!}</label>

    <div class="row">
        @if(isset($field['permissions_data']) && count($field['permissions_data']) > 0)
            @foreach($field['permissions_data'] as $permission_id => $permission_label)
                <div class="col-md-4 mb-2">
                    <div class="form-check">
                        <input 
                            class="form-check-input" 
                            type="checkbox" 
                            name="{{ $field['name'] }}[]" 
                            value="{{ $permission_id }}"
                            id="permission_{{ $permission_id }}"
                            @if(in_array($permission_id, $selected_permissions)) checked @endif
                        >
                        <label class="form-check-label" for="permission_{{ $permission_id }}">
                            {{ $permission_label }}
                        </label>
                    </div>
                </div>
            @endforeach
        @else
            <div class="col-12">
                <p class="text-muted">Tidak ada permissions tersedia.</p>
            </div>
        @endif
    </div>

    {{-- Show a hint if available --}}
    @if (isset($field['hint']))
        <small class="form-text text-muted">{!! $field['hint'] !!}</small>
    @endif
</div>
