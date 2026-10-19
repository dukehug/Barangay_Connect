/** Crop and preview a photo for the authenticated profile upload. */
(() => {
  const profilePhoto = document.getElementById("profilePhoto");
  const sidebarAvatar = document.querySelector(".user .avatar");
  const photoInput = document.getElementById("photoInput");
  const placeholderPath = document.getElementById("profilePhoto").dataset.placeholder;
  const placeholderUrl = new URL(placeholderPath, document.baseURI).href;
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
    },
    { once: true },
  );
  profilePhoto.addEventListener("load", updateSidebarPhoto);

  // A cached image may already have loaded before this deferred script executes.
  if (profilePhoto.complete && profilePhoto.naturalWidth > 0)
    updateSidebarPhoto();

  const editor = document.getElementById("photoEditor");
  const canvas = document.getElementById("photoCanvas");
  const context = canvas.getContext("2d");
  const zoom = document.getElementById("photoZoom");
  const message = document.getElementById("photoMessage");
  const error = document.getElementById("photoEditorError");
  let source = null;
  let offsetX = 0, offsetY = 0, baseScale = 1;
  let drag = null;
  let loadVersion = 0;

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
    document.getElementById("photoZoomValue").textContent = `${Math.round(Number(zoom.value) * 100)}%`;
  }
  function reset() { offsetX = offsetY = 0; zoom.value = "1"; draw(); }
  zoom.addEventListener("input", draw);
  document.getElementById("resetPhoto").addEventListener("click", reset);
  for (const id of ["closePhotoEditor", "cancelPhoto"]) {
    document.getElementById(id).addEventListener("click", () => editor.close());
  }
  editor.addEventListener("close", () => {
    source = null; drag = null; photoInput.value = "";
    photoInput.focus();
  });
  canvas.addEventListener("pointerdown", event => {
    if (event.button !== 0 || drag) return;
    drag = { id: event.pointerId, x: event.clientX, y: event.clientY };
    canvas.setPointerCapture(event.pointerId);
  });
  canvas.addEventListener("pointermove", event => {
    if (!drag || drag.id !== event.pointerId) return;
    const ratio = photoSize / canvas.getBoundingClientRect().width;
    offsetX += (event.clientX - drag.x) * ratio;
    offsetY += (event.clientY - drag.y) * ratio;
    drag.x = event.clientX; drag.y = event.clientY;
    draw();
  });
  for (const name of ["pointerup", "pointercancel", "lostpointercapture"]) {
    canvas.addEventListener(name, event => { if (drag?.id === event.pointerId) drag = null; });
  }
  canvas.addEventListener("keydown", event => {
    const moves = { ArrowLeft: [-1, 0], ArrowRight: [1, 0], ArrowUp: [0, -1], ArrowDown: [0, 1] };
    if (!moves[event.key]) return;
    event.preventDefault();
    const step = event.shiftKey ? 20 : 5;
    offsetX += moves[event.key][0] * step; offsetY += moves[event.key][1] * step;
    draw();
  });
  document.getElementById("savePhoto").addEventListener("click", () => {
    if (!source || !context) return;
    try {
      const data = canvas.toDataURL("image/jpeg", 0.85);
      const bytes = atob(data.split(',')[1]);
      const buffer = Uint8Array.from(bytes, char => char.charCodeAt(0));
      const transfer = new DataTransfer();
      transfer.items.add(new File([buffer], 'profile.jpg', { type: 'image/jpeg' }));
      document.querySelector('[data-cropped-photo]').files = transfer.files;
      profilePhoto.src = data;
      message.textContent = "Photo prepared. Save your profile to upload it.";
      editor.close();
      document.getElementById("profileEdit").showModal();
    } catch {
      error.textContent = "Unable to prepare your photo. Your previous photo is unchanged. Please try again.";
    }
  });
  photoInput.addEventListener("change", () => {
    const file = photoInput.files[0];
    if (!file) return;
    const version = ++loadVersion;
    message.textContent = "";
    if (!["image/jpeg", "image/png", "image/webp"].includes(file.type) || file.size > 5 * 1024 * 1024) {
      message.textContent = "Choose a JPG, PNG, or WebP image up to 5 MB.";
      photoInput.value = ""; return;
    }
    const url = URL.createObjectURL(file);
    const image = new Image();
    image.onload = () => {
      URL.revokeObjectURL(url);
      if (version !== loadVersion) return;
      if (!context || !image.naturalWidth || !image.naturalHeight) {
        message.textContent = "Photo editing is unavailable for this image.";
        photoInput.value = ""; return;
      }
      source = image;
      baseScale = Math.max(photoSize / image.naturalWidth, photoSize / image.naturalHeight);
      error.textContent = "";
      reset();
      editor.showModal();
    };
    image.onerror = () => {
      URL.revokeObjectURL(url);
      if (version !== loadVersion) return;
      message.textContent = "This image could not be opened. Please choose another photo.";
      photoInput.value = "";
    };
    image.src = url;
  });
})();
