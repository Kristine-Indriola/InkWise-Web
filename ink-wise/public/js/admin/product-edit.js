document.addEventListener('DOMContentLoaded', function(){
    // Product type change handler for dynamic labels
    const productTypeSelect = document.getElementById('productType');

    if(productTypeSelect){
        productTypeSelect.addEventListener('change', function(){
            const selectedType = this.value;
            const productNameLabel = document.getElementById('productNameLabel');
            const basicInfoHeader = document.getElementById('basicInfoHeader');

            // Update product name label based on selected type
            if(productNameLabel){
                productNameLabel.textContent = selectedType + ' Name *';
            }

            // Update basic information header based on selected type
            if(basicInfoHeader){
                basicInfoHeader.textContent = selectedType + ' Information';
            }
        });
    }

    // Dynamic row management functions
    function addDynamicRow(containerId, rowClass, templateFunction, addButtonId, removeButtonClass){
        const container = document.getElementById(containerId);
        const addButton = document.getElementById(addButtonId);
        let rowIndex = container.querySelectorAll('.' + rowClass).length;

        if(addButton){
            addButton.addEventListener('click', function(){
                const newRow = templateFunction(rowIndex);
                container.insertAdjacentHTML('beforeend', newRow);
                rowIndex++;
                updateRemoveButtons(container, removeButtonClass);
            });
        }
    }

    function updateRemoveButtons(container, removeButtonClass){
        const rows = container.querySelectorAll('.' + removeButtonClass.split(' ')[0]);
        rows.forEach((row, index) => {
            const removeBtn = row.querySelector('.' + removeButtonClass);
            if(removeBtn){
                removeBtn.disabled = index === 0;
            }
        });
    }

    // Template functions for different row types
    function paperStockTemplate(index){
        return `
            <div class="dynamic-row paper-stock-row" data-index="${index}">
                <div class="form-grid">
                    <div class="field">
                        <label>Material *</label>
                        <select name="paper_stocks[${index}][material_id]" required>
                            <option value="">Select material</option>
                            @foreach($materials as $material)
                                <option value="{{ $material->id }}">{{ $material->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>Name *</label>
                        <input type="text" name="paper_stocks[${index}][name]" required>
                    </div>
                    <div class="field">
                        <label>Price *</label>
                        <input type="number" step="0.01" name="paper_stocks[${index}][price]" required>
                    </div>
                    <div class="field">
                        <label>Image</label>
                        <input type="file" name="paper_stocks[${index}][image]" accept="image/*">
                    </div>
                    <div class="field actions">
                        <button type="button" class="btn-remove remove-paper-stock">Remove</button>
                    </div>
                </div>
            </div>
        `;
    }

    function addonTemplate(index){
        return `
            <div class="dynamic-row addon-row" data-index="${index}">
                <div class="form-grid">
                    <div class="field">
                        <label>Type *</label>
                        <select name="addons[${index}][addon_type]" required>
                            <option value="">Select type</option>
                            <option value="Printing">Printing</option>
                            <option value="Embellishment">Embellishment</option>
                            <option value="Packaging">Packaging</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Name *</label>
                        <input type="text" name="addons[${index}][name]" required>
                    </div>
                    <div class="field">
                        <label>Price *</label>
                        <input type="number" step="0.01" name="addons[${index}][price]" required>
                    </div>
                    <div class="field">
                        <label>Image</label>
                        <input type="file" name="addons[${index}][image]" accept="image/*">
                    </div>
                    <div class="field actions">
                        <button type="button" class="btn-remove remove-addon">Remove</button>
                    </div>
                </div>
            </div>
        `;
    }


    // Initialize dynamic sections
    addDynamicRow('paper-stocks-container', 'paper-stock-row', paperStockTemplate, 'add-paper-stock', 'remove-paper-stock');
    addDynamicRow('addons-container', 'addon-row', addonTemplate, 'add-addon', 'remove-addon');

        // Show/hide envelope fields when product type changes
        const productTypeSelect = document.getElementById('productType');
        const envelopeFields = document.getElementById('envelope-fields');
        if (productTypeSelect && envelopeFields) {
            productTypeSelect.addEventListener('change', function () {
                if (this.value === 'Envelope') {
                    envelopeFields.style.display = 'block';
                    // Make required
                    document.getElementById('materialType').required = true;
                    document.getElementById('envelopeMaterial').required = true;
                } else {
                    envelopeFields.style.display = 'none';
                    document.getElementById('materialType').required = false;
                    document.getElementById('envelopeMaterial').required = false;
                }
            });
        }

        // Auto-calc Date Available from Lead Time (days)
        const leadTimeInput = document.getElementById('leadTime');
        const dateAvailableInput = document.getElementById('dateAvailable');
        function updateDateAvailableFromLeadTime() {
            if (!leadTimeInput || !dateAvailableInput) return;
            const val = leadTimeInput.value;
            const days = parseInt(val, 10);
            if (isNaN(days) || days < 0) {
                // Clear if invalid
                dateAvailableInput.value = '';
                return;
            }
            const today = new Date();
            // Add days (consider only whole days)
            today.setDate(today.getDate() + days);
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, '0');
            const dd = String(today.getDate()).padStart(2, '0');
            dateAvailableInput.value = `${yyyy}-${mm}-${dd}`;
        }
        if (leadTimeInput) {
            leadTimeInput.addEventListener('input', updateDateAvailableFromLeadTime);
            // also update on blur to finalize
            leadTimeInput.addEventListener('blur', updateDateAvailableFromLeadTime);
        }

    // Remove button event delegation
    document.addEventListener('click', function(e){
        if(e.target.classList.contains('btn-remove')){
            const row = e.target.closest('.dynamic-row');
            if(row){
                const container = row.parentElement;
                row.remove();
                // Re-index remaining rows
                const rows = container.querySelectorAll('.dynamic-row');
                rows.forEach((r, index) => {
                    r.setAttribute('data-index', index);
                    const inputs = r.querySelectorAll('input, select');
                    inputs.forEach(input => {
                        const name = input.name;
                        if(name){
                            const newName = name.replace(/\[\d+\]/, `[${index}]`);
                            input.name = newName;
                        }
                    });
                });
                updateRemoveButtons(container, e.target.className.split(' ').find(c => c.startsWith('remove-')));
            }
        }
    });

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
                alert('Please fill required fields: Product Name, Event Type, Product Type and Theme/Style');
                name.focus();
                return;
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
