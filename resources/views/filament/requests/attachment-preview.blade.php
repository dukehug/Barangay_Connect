@php($attachment = $getRecord())
<div style="height: 160px; display: grid; place-items: center; background: #f1f5f9; border-radius: 10px; overflow: hidden;">
    @if($attachment->isImage())
        <img src="{{ $attachment->previewUrl() }}" alt="{{ $attachment->name }}" loading="lazy" style="width: 100%; height: 160px; object-fit: contain;" />
    @else
        <span style="color: #9f3a41; font-weight: 600;">▤ PDF</span>
    @endif
</div>
