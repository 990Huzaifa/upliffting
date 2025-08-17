@props([
  'form_id',
  'id',              // e.g. "suspendReasonModal"
  'title' => '',     // Modal title
  'size' => 'md'     // bs modal-sm | modal-md | modal-lg | modal-xl
])

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}Label" aria-hidden="true">
  <div class="modal-dialog modal-{{ $size }}">
    <form {{ $attributes->merge(['class' => 'modal-content']) }} id="{{ $form_id }}" method="POST">
      <div class="modal-header">
        <h5 class="modal-title" id="{{ $id }}Label">{{ $title }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        {{ $slot }}
      </div>
      @if(isset($footer))
        <div class="modal-footer">
          {{ $footer }}
        </div>
      @endif
    </form>
  </div>
</div>
