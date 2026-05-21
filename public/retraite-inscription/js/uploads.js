/* ═══════════════════════════════════════════
   PHOTO & PROOF UPLOADS
═══════════════════════════════════════════ */

/* ─── IMAGE COMPRESSION ─── */
const MAX_PHOTO_BYTES = 3 * 1024 * 1024; /* 3 Mo */
const MAX_PHOTO_DIMENSION = 1200;

function showCompressStatus(type, msg) {
  const el = document.getElementById('photoCompressStatus');
  if (!el) return;
  el.innerHTML = type === 'compressing'
    ? `<div class="photo-compress-status compressing"><span class="compress-spinner"></span> ${msg}</div>`
    : `<div class="photo-compress-status done"><i class="bi bi-check-circle"></i> ${msg}</div>`;
}
function clearCompressStatus() {
  const el = document.getElementById('photoCompressStatus');
  if (el) el.innerHTML = '';
}

function compressImage(file, maxBytes, maxDim) {
  return new Promise((resolve) => {
    const img = new Image();
    const url = URL.createObjectURL(file);
    img.onload = () => {
      URL.revokeObjectURL(url);
      let { width, height } = img;
      if (width > maxDim || height > maxDim) {
        const ratio = Math.min(maxDim / width, maxDim / height);
        width = Math.round(width * ratio);
        height = Math.round(height * ratio);
      }
      const canvas = document.createElement('canvas');
      canvas.width = width;
      canvas.height = height;
      const ctx = canvas.getContext('2d');
      ctx.drawImage(img, 0, 0, width, height);
      let quality = 0.9;
      const tryCompress = () => {
        canvas.toBlob((blob) => {
          if (!blob) {
            resolve(null);
            return;
          }
          if (blob.size <= maxBytes || quality <= 0.1) {
            const reader = new FileReader();
            reader.onload = (e) => resolve({
              dataURL: e.target.result,
              originalSize: file.size,
              compressedSize: blob.size,
              wasCompressed: file.size > maxBytes
            });
            reader.readAsDataURL(blob);
          } else {
            quality -= 0.1;
            tryCompress();
          }
        }, 'image/jpeg', quality);
      };
      tryCompress();
    };
    img.onerror = () => { URL.revokeObjectURL(url); resolve(null); };
    img.src = url;
  });
}

function formatBytes(bytes) {
  if (bytes < 1024) return bytes + ' o';
  if (bytes < 1048576) return (bytes / 1024).toFixed(0) + ' Ko';
  return (bytes / 1048576).toFixed(1) + ' Mo';
}

/* ─── INIT PHOTO UPLOAD ─── */
function initPhotoUpload() {
  const photoInput = document.getElementById('photoInput');
  const photoZone = document.getElementById('photoZone');
  const photoPreview = document.getElementById('photoPreview');
  const photoPlaceholder = document.getElementById('photoPlaceholder');
  const photoRemoveBtn = document.getElementById('photoRemoveBtn');

  const photoCropModal = document.getElementById('photoCropModal');
  const photoCropBackdrop = document.getElementById('photoCropBackdrop');
  const photoCropClose = document.getElementById('photoCropClose');
  const photoCropCancel = document.getElementById('photoCropCancel');
  const photoCropApply = document.getElementById('photoCropApply');
  const photoCropImage = document.getElementById('photoCropImage');
  const photoCropZoom = document.getElementById('photoCropZoom');

  if (!photoInput || !photoZone || !photoPreview || !photoPlaceholder || !photoRemoveBtn) return;
  const photoRequiredError = document.getElementById('photoRequiredError');

  async function processPhotoFile(file) {
    if (!file || !file.type.startsWith('image/')) return;

    if (file.size > MAX_PHOTO_BYTES) {
      showCompressStatus('compressing', `Compression en cours (${formatBytes(file.size)})...`);
      const result = await compressImage(file, MAX_PHOTO_BYTES, MAX_PHOTO_DIMENSION);
      if (!result) {
        clearCompressStatus();
      retraiteNotifyToast('Impossible de traiter cette image. Essayez un autre fichier.', 'warning');
        return;
      }
      App.photoDataURL = result.dataURL;
      showCompressStatus('done', `Compressee : ${formatBytes(result.originalSize)} -> ${formatBytes(result.compressedSize)}`);
    } else {
      const result = await compressImage(file, MAX_PHOTO_BYTES, MAX_PHOTO_DIMENSION);
      if (!result) {
        clearCompressStatus();
      retraiteNotifyToast('Impossible de traiter cette image. Essayez un autre fichier.', 'warning');
        return;
      }
      App.photoDataURL = result.dataURL;
      if (result.wasCompressed) {
        showCompressStatus('done', `Optimisee : ${formatBytes(result.compressedSize)}`);
      } else {
        clearCompressStatus();
      }
    }

    photoPreview.src = App.photoDataURL;
    photoPreview.style.display = 'block';
    photoPlaceholder.style.display = 'none';
    photoZone.classList.add('has-photo');
    photoZone.classList.remove('is-error');
    if (photoRequiredError) photoRequiredError.classList.remove('visible');
    photoRemoveBtn.classList.remove('hidden');
  }

  function openCropper(file) {
    if (
      !photoCropModal ||
      !photoCropBackdrop ||
      !photoCropClose ||
      !photoCropCancel ||
      !photoCropApply ||
      !photoCropImage ||
      !photoCropZoom ||
      typeof Cropper === 'undefined'
    ) {
      return Promise.resolve(file);
    }

    return new Promise((resolve) => {
      let cropper = null;
      let objectUrl = URL.createObjectURL(file);
      let lastZoomValue = 0;

      const cleanup = () => {
        if (cropper) {
          cropper.destroy();
          cropper = null;
        }
        if (objectUrl) {
          URL.revokeObjectURL(objectUrl);
          objectUrl = null;
        }
        photoCropZoom.removeEventListener('input', onZoomInput);
        photoCropBackdrop.removeEventListener('click', cancelCrop);
        photoCropClose.removeEventListener('click', cancelCrop);
        photoCropCancel.removeEventListener('click', cancelCrop);
        photoCropApply.removeEventListener('click', applyCrop);
        photoCropImage.removeEventListener('load', initCropper);
        photoCropModal.classList.add('hidden');
        photoCropModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('crop-modal-open');
      };

      const onZoomInput = () => {
        if (!cropper) return;
        const nextValue = Number(photoCropZoom.value);
        const delta = (nextValue - lastZoomValue) / 100;
        cropper.zoom(delta);
        lastZoomValue = nextValue;
      };

      const cancelCrop = () => {
        cleanup();
        photoInput.value = '';
        resolve(null);
      };

      const applyCrop = () => {
        if (!cropper) {
          cleanup();
          resolve(file);
          return;
        }

        const canvas = cropper.getCroppedCanvas({
          width: 900,
          height: 900,
          imageSmoothingQuality: 'high',
          fillColor: '#ffffff'
        });

        canvas.toBlob((blob) => {
          cleanup();
          if (!blob) {
            resolve(file);
            return;
          }
          resolve(new File([blob], `photo-crop-${Date.now()}.jpg`, { type: 'image/jpeg' }));
        }, 'image/jpeg', 0.92);
      };

      const initCropper = () => {
        if (cropper) cropper.destroy();
        cropper = new Cropper(photoCropImage, {
          aspectRatio: 1,
          viewMode: 1,
          dragMode: 'move',
          autoCropArea: 1,
          guides: false,
          center: false,
          highlight: false,
          cropBoxMovable: true,
          cropBoxResizable: false,
          toggleDragModeOnDblclick: false,
          responsive: true,
        });
      };

      photoCropImage.addEventListener('load', initCropper);
      photoCropZoom.addEventListener('input', onZoomInput);
      photoCropBackdrop.addEventListener('click', cancelCrop);
      photoCropClose.addEventListener('click', cancelCrop);
      photoCropCancel.addEventListener('click', cancelCrop);
      photoCropApply.addEventListener('click', applyCrop);

      photoCropZoom.value = '0';
      lastZoomValue = 0;
      photoCropImage.src = objectUrl;
      photoCropModal.classList.remove('hidden');
      photoCropModal.setAttribute('aria-hidden', 'false');
      document.body.classList.add('crop-modal-open');
    });
  }

  photoZone.addEventListener('click', (e) => {
    if (e.target.closest('.photo-remove-btn')) return;
    photoInput.click();
  });

  photoInput.addEventListener('change', async (e) => {
    const file = e.target.files[0];
    if (!file || !file.type.startsWith('image/')) return;

    const croppedFile = await openCropper(file);
    if (!croppedFile) return;

    await processPhotoFile(croppedFile);
  });

  photoRemoveBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    App.photoDataURL = null;
    clearCompressStatus();
    photoPreview.src = '';
    photoPreview.style.display = 'none';
    photoPlaceholder.style.display = '';
    photoZone.classList.remove('has-photo');
    photoZone.classList.remove('is-error');
    if (photoRequiredError) photoRequiredError.classList.remove('visible');
    photoRemoveBtn.classList.add('hidden');
    photoInput.value = '';
  });
}

/* ─── INIT PROOF UPLOAD ─── */
function initProofUpload() {
  const proofInput = document.getElementById('proofInput');
  const proofDropZone = document.getElementById('proofDropZone');
  const proofPreview = document.getElementById('proofPreview');
  const proofImage = document.getElementById('proofImage');
  const proofFileName = document.getElementById('proofFileName');
  const proofRemoveBtn = document.getElementById('proofRemoveBtn');
  const proofError = document.getElementById('proofError');

  if (!proofInput || !proofDropZone || !proofPreview || !proofImage || !proofFileName || !proofRemoveBtn || !proofError) {
    return;
  }

  proofDropZone.addEventListener('click', () => proofInput.click());

  proofDropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    proofDropZone.classList.add('dragover');
  });
  proofDropZone.addEventListener('dragleave', () => proofDropZone.classList.remove('dragover'));
  proofDropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    proofDropZone.classList.remove('dragover');
    if (e.dataTransfer.files.length) handleProofFile(e.dataTransfer.files[0]);
  });

  proofInput.addEventListener('change', (e) => {
    if (e.target.files.length) handleProofFile(e.target.files[0]);
  });

  function handleProofFile(file) {
    if (file.size > 5 * 1024 * 1024) {
      retraiteNotifyToast('Le fichier ne doit pas dépasser 5 Mo.', 'warning');
      return;
    }
    App.proofFile = file;
    proofError.classList.remove('visible');
    proofDropZone.classList.add('has-file');
    proofPreview.classList.add('visible');
    proofFileName.querySelector('span').textContent = file.name;

    if (file.type.startsWith('image/')) {
      const reader = new FileReader();
      reader.onload = (evt) => {
        App.proofDataURL = evt.target.result;
        proofImage.src = App.proofDataURL;
        proofImage.style.display = 'block';
      };
      reader.readAsDataURL(file);
    } else {
      App.proofDataURL = null;
      proofImage.style.display = 'none';
    }
  }

  proofRemoveBtn.addEventListener('click', () => {
    App.proofFile = null;
    App.proofDataURL = null;
    proofInput.value = '';
    proofDropZone.classList.remove('has-file');
    proofPreview.classList.remove('visible');
    proofImage.style.display = 'none';
    proofFileName.querySelector('span').textContent = '';
  });
}
