@php
    $accept = $accept ?? 'image/*,application/pdf';
    $capture = $capture ?? 'environment';
    $required = $required ?? true;
    $allowsPdf = str_contains($accept, 'pdf');
    $maxKb = $maxKb ?? ($allowsPdf ? 8192 : 5120);
    $maxMb = (int) round($maxKb / 1024);
    $formatLabel = $allowsPdf ? 'JPG, PNG, WEBP o PDF' : 'JPG, PNG o WEBP';
    $help = $help ?? 'Toma una foto o sube un archivo.';
@endphp

<div
    class="doc-upload @error($name) is-invalid @enderror"
    data-doc-upload
    data-doc-label="{{ $label }}"
    data-max-kb="{{ $maxKb }}"
    data-allows-pdf="{{ $allowsPdf ? 'true' : 'false' }}"
    data-format-label="{{ $formatLabel }}"
>
    <label class="form-label" for="{{ $id }}">{{ $label }}</label>
    <p class="small text-muted mb-1">{{ $help }}</p>
    <p class="small mb-2 doc-upload__specs">
        Formato: <strong>{{ $formatLabel }}</strong>
        · Tamaño máximo: <strong>{{ $maxMb }} MB</strong>
    </p>

    <input
        type="file"
        id="{{ $id }}"
        name="{{ $name }}"
        class="doc-upload__input"
        accept="{{ $accept }}"
        data-doc-file-input
        @required($required)
    >
    <input
        type="file"
        class="doc-upload__input"
        accept="image/*"
        capture="{{ $capture }}"
        data-doc-camera-input
        tabindex="-1"
    >

    <div class="doc-upload__actions">
        <button type="button" class="btn btn-brand-outline btn-sm d-inline-flex align-items-center gap-1" data-doc-camera>
            <i class="bi bi-camera"></i>
            Tomar foto
        </button>
        <button type="button" class="btn btn-brand-outline btn-sm d-inline-flex align-items-center gap-1" data-doc-file>
            <i class="bi bi-upload"></i>
            Subir archivo
        </button>
    </div>

    <div class="doc-upload__preview d-none" data-doc-preview>
        <img src="" alt="Vista previa" class="d-none" data-doc-image>
        <span class="doc-upload__file-icon d-none" data-doc-icon><i class="bi bi-file-earmark-pdf"></i></span>
        <span class="doc-upload__filename small" data-doc-filename></span>
        <button type="button" class="btn btn-link btn-sm text-danger p-0 ms-auto" data-doc-clear>Quitar</button>
    </div>

    <div class="invalid-feedback d-none" data-doc-error></div>

    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
