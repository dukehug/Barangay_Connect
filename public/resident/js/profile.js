/** Crop and preview a photo for the authenticated profile upload. */
(() => {
    const profilePhoto = document.getElementById("profilePhoto");
    const sidebarAvatar = document.querySelector(".user .avatar");
    const photoInput = document.getElementById("photoInput");
    const placeholderPath = document.getElementById("profilePhoto").dataset.placeholder;
    const placeholderUrl = new URL(placeholderPath, document.baseURI).href;
    const profilePicker = document.getElementById("photo");
    const profileDialog = document.getElementById("profileEdit");
    const preparedInput = document.querySelector('[data-cropped-photo]');
    const photoSize = 300;

    // Synchronize a loaded resident photo with the sidebar, preserving its default state otherwise.
    function updateSidebarPhoto() {
        if (profilePhoto.src !== placeholderUrl) {
            sidebarAvatar.style.backgroundImage = `url("${profilePhoto.src}")`;
        }
    }

    // Recover from an invalid saved image without repeatedly retrying a broken source.
    profilePhoto.addEventListener(
        "error",
        () => {
            profilePhoto.src = placeholderPath;
            sidebarAvatar.style.backgroundImage = "";
        }, {
            once: true
        },
    );
    profilePhoto.addEventListener("load", updateSidebarPhoto);

    // A cached image may already have loaded before this deferred script executes.
    if (profilePhoto.complete && profilePhoto.naturalWidth > 0)
        updateSidebarPhoto();

    const editor = document.getElementById("photoEditor");
    const canvas = document.getElementById("photoCanvas");
    const context = canvas.getContext("2d");
    const zoom = document.getElementById("photoZoom");
    const status = document.getElementById("photoEditorStatus");
    const savePhoto = document.getElementById("savePhoto");
    const zoomOut = document.getElementById("zoomOutPhoto");
    const zoomIn = document.getElementById("zoomInPhoto");
    const resetPhoto = document.getElementById("resetPhoto");
    let activePicker = photoInput;
    let source = null;
    let offsetX = 0,
        offsetY = 0,
        baseScale = 1;
    let drag = null;
    let loadVersion = 0;

    function showPhotoAlert(icon, title, text) {
        return window.ResidentAlerts.fire({
            icon,
            title,
            text,
            target: editor.open ? editor : profileDialog.open ? profileDialog : document.body,
        });
    }

    function updateControls() {
        const unavailable = !source || !context;
        savePhoto.disabled = unavailable;
        resetPhoto.disabled = unavailable;
        zoom.disabled = unavailable;
        zoomOut.disabled = unavailable || Number(zoom.value) <= Number(zoom.min);
        zoomIn.disabled = unavailable || Number(zoom.value) >= Number(zoom.max);
    }

    function draw() {
        if (!source || !context) return;
        const scale = baseScale * Number(zoom.value);
        const width = source.naturalWidth * scale;
        const height = source.naturalHeight * scale;
        offsetX = Math.max((photoSize - width) / 2, Math.min((width - photoSize) / 2, offsetX));
        offsetY = Math.max((photoSize - height) / 2, Math.min((height - photoSize) / 2, offsetY));
        context.fillStyle = "#fff";
        context.fillRect(0, 0, photoSize, photoSize);
        context.drawImage(source, (photoSize - width) / 2 + offsetX, (photoSize - height) / 2 + offsetY, width, height);
        updateControls();
        document.getElementById("photoZoomValue").textContent = `${Math.round(Number(zoom.value) * 100)}%`;
    }

    function reset() {
        offsetX = offsetY = 0;
        zoom.value = "1";
        draw();
    }
    zoom.addEventListener("input", draw);
    for (const [button, step] of [
            [zoomOut, -0.1],
            [zoomIn, 0.1]
        ]) {
        button.addEventListener("click", () => {
            zoom.value = Math.max(Number(zoom.min), Math.min(Number(zoom.max), Number(zoom.value) + step));
            draw();
        });
    }
    document.getElementById("resetPhoto").addEventListener("click", reset);
    for (const id of ["closePhotoEditor", "cancelPhoto"]) {
        document.getElementById(id).addEventListener("click", () => editor.close());
    }
    editor.addEventListener("close", () => {
        loadVersion += 1;
        source = null;
        drag = null;
        activePicker.value = "";
        updateControls();
        if (!profileDialog.open) activePicker.focus();
    });
    canvas.addEventListener("pointerdown", event => {
        if (event.button !== 0 || drag) return;
        drag = {
            id: event.pointerId,
            x: event.clientX,
            y: event.clientY
        };
        canvas.setPointerCapture(event.pointerId);
    });
    canvas.addEventListener("pointermove", event => {
        if (!drag || drag.id !== event.pointerId) return;
        const ratio = photoSize / canvas.getBoundingClientRect().width;
        offsetX += (event.clientX - drag.x) * ratio;
        offsetY += (event.clientY - drag.y) * ratio;
        drag.x = event.clientX;
        drag.y = event.clientY;
        draw();
    });
    for (const name of ["pointerup", "pointercancel", "lostpointercapture"]) {
        canvas.addEventListener(name, event => {
            if (drag?.id === event.pointerId) drag = null;
        });
    }
    canvas.addEventListener("keydown", event => {
        const moves = {
            ArrowLeft: [-1, 0],
            ArrowRight: [1, 0],
            ArrowUp: [0, -1],
            ArrowDown: [0, 1]
        };
        if (!moves[event.key]) return;
        event.preventDefault();
        const step = event.shiftKey ? 20 : 5;
        offsetX += moves[event.key][0] * step;
        offsetY += moves[event.key][1] * step;
        draw();
    });
    document.getElementById("savePhoto").addEventListener("click", () => {
        if (!source || !context) return;
        try {
            const data = canvas.toDataURL("image/jpeg", 0.85);
            const bytes = atob(data.split(',')[1]);
            const buffer = Uint8Array.from(bytes, char => char.charCodeAt(0));
            const transfer = new DataTransfer();
            transfer.items.add(new File([buffer], 'profile.jpg', {
                type: 'image/jpeg'
            }));
            preparedInput.files = transfer.files;
            profilePhoto.src = data;
            document.getElementById("preparedPhoto").src = data;
            document.getElementById("profilePhotoPreview").hidden = false;
            editor.close();
            if (!profileDialog.open) profileDialog.showModal();
        } catch {
            showPhotoAlert("error", "Unable to prepare photo", "Unable to prepare your photo. Your previous photo is unchanged. Please try again.");
        }
    });
    for (const picker of [photoInput, profilePicker]) {
        picker.addEventListener("change", () => {
            const file = picker.files[0];
            if (!file) return;
            activePicker = picker;
            const version = ++loadVersion;
            if (!["image/jpeg", "image/png", "image/webp"].includes(file.type) || file.size > 5 * 1024 * 1024) {
                showPhotoAlert("warning", "Invalid photo", "Choose a JPG, PNG, or WebP image up to 5 MB.");
                picker.value = "";
                return;
            }
            source = null;
            status.textContent = "Loading your photo…";
            context?.clearRect(0, 0, photoSize, photoSize);
            updateControls();
            editor.showModal();
            const url = URL.createObjectURL(file);
            const image = new Image();
            image.onload = () => {
                URL.revokeObjectURL(url);
                if (version !== loadVersion) return;
                status.textContent = "";
                if (!context || !image.naturalWidth || !image.naturalHeight) {
                    showPhotoAlert("error", "Unable to edit photo", "Photo editing is unavailable for this image. Cancel and choose another photo.");
                    return;
                }
                source = image;
                baseScale = Math.max(photoSize / image.naturalWidth, photoSize / image.naturalHeight);
                reset();
                status.textContent = "Drag the photo to reposition it. Use − / + or the slider to zoom, then select Use this photo.";
                canvas.focus();
            };
            image.onerror = () => {
                URL.revokeObjectURL(url);
                if (version !== loadVersion) return;
                status.textContent = "";
                showPhotoAlert("error", "Unable to open photo", "This image could not be opened. Cancel and choose another photo.");
            };
            image.src = url;
        });
    }
})();
