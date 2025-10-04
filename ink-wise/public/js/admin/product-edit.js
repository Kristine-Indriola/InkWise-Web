document.addEventListener('DOMContentLoaded', function(){
    const imageInput = document.getElementById('image');
    const previewContainer = document.querySelector('.image-preview');

    if(imageInput){
        imageInput.addEventListener('change', function(e){
            const file = e.target.files && e.target.files[0];
            if(!file) return;
            const reader = new FileReader();
            reader.onload = function(ev){
                if(!previewContainer) return;
                previewContainer.innerHTML = '';
                const img = document.createElement('img');
                img.src = ev.target.result;
                img.alt = 'Preview';
                previewContainer.appendChild(img);
            };
            reader.readAsDataURL(file);
        });
    }

    // Simple client-side validation for required fields
    const form = document.getElementById('product-edit-form');
    if(form){
        form.addEventListener('submit', function(e){
            const name = document.getElementById('invitationName');
            const eventType = document.getElementById('eventType');
            const productType = document.getElementById('productType');
            const themeStyle = document.getElementById('themeStyle');
            if(!name.value.trim() || !eventType.value || !productType.value || !themeStyle.value.trim()){
                e.preventDefault();
                alert('Please fill required fields: Invitation Name, Event Type, Product Type and Theme/Style');
                name.focus();
            }
        });
    }

        // Upload additional product file via AJAX
        const uploadBtn = document.getElementById('uploadBtn');
        const uploadFileInput = document.getElementById('productUploadFile');
        const uploadStatus = document.getElementById('uploadStatus');
        const uploadsList = document.getElementById('uploads-list');

        if (uploadBtn && uploadFileInput) {
            uploadBtn.addEventListener('click', function () {
                const file = uploadFileInput.files && uploadFileInput.files[0];
                if (!file) {
                    uploadStatus.textContent = 'Please choose a file first.';
                    return;
                }

                // Determine product ID from hidden input
                const productIdInput = document.querySelector('input[name="product_id"]');
                const productId = productIdInput ? productIdInput.value : null;
                if (!productId) {
                    uploadStatus.textContent = 'Save the product first before uploading files.';
                    return;
                }

                uploadStatus.textContent = 'Uploading...';
                const formData = new FormData();
                formData.append('file', file);
                // CSRF token meta tag fallback
                let token = null;
                const meta = document.querySelector('meta[name="csrf-token"]');
                if (meta) token = meta.getAttribute('content');
                // Fallback: hidden _token input inside form
                if (!token) {
                    const formToken = document.querySelector('input[name="_token"]');
                    if (formToken) token = formToken.value;
                }

                fetch(`/admin/products/${productId}/upload`, {
                    method: 'POST',
                    headers: token ? { 'X-CSRF-TOKEN': token } : {},
                    body: formData,
                }).then(async (res) => {
                    if (!res.ok) {
                        const data = await res.json().catch(() => ({}));
                        throw new Error(data.message || 'Upload failed');
                    }
                    return res.json();
                }).then((data) => {
                    uploadStatus.textContent = 'Upload successful.';
                    // Append to uploads list
                    if (data.upload) {
                        const li = document.createElement('li');
                        const a = document.createElement('a');
                        const url = `/storage/uploads/products/${productId}/${data.upload.filename}`;
                        a.href = url;
                        a.target = '_blank';
                        a.textContent = data.upload.original_name || data.upload.filename;
                        li.appendChild(a);
                        const small = document.createElement('small');
                        small.className = 'text-muted';
                        small.style.marginLeft = '8px';
                        small.textContent = `(${(data.upload.size/1024).toFixed(2)} KB)`;
                        li.appendChild(small);
                        // If there's an existing UL in uploadsList, append, otherwise create
                        let ul = uploadsList.querySelector('ul');
                        if (!ul) {
                            ul = document.createElement('ul');
                            uploadsList.appendChild(ul);
                        }
                        ul.appendChild(li);
                    }
                }).catch((err) => {
                    uploadStatus.textContent = err.message || 'Upload failed.';
                });
            });
        }
});
