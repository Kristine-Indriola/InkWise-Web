{{-- filepath: c:\xampp\htdocs\InkWise-Web\ink-wise\resources\views\admin\products\templates.blade.php --}}
{{-- Page 1: Templates --}}
<div class="page page1" data-page="1">
    <h2>Invitation Templates</h2>
    <p>Select a template to start your product.</p>
    <div class="templates-container">
        <div class="templates-grid">
            @foreach($templates as $template)
                <div class="template-card" tabindex="0" role="button" data-template-id="{{ $template->id }}">
                    <div class="template-image-container">
                        @if($template->preview)
                            <img src="{{ asset('storage/' . $template->preview) }}" alt="{{ $template->name }}" class="template-img">
                        @else
                            <span>No preview</span>
                        @endif
                    </div>
                    <div class="card-overlay">
                        <h3>{{ $template->name }}</h3>
                        <p>{{ $template->description }}</p>
                        <div class="card-actions">
                            <button type="button" class="btn continue-btn"
                                data-template-id="{{ $template->id }}"
                                data-template-name="{{ $template->name }}"
                                data-template-description="{{ $template->description }}"
                                data-template-event_type="{{ $template->event_type }}"
                                data-template-product_type="{{ $template->product_type }}"
                                data-template-theme_style="{{ $template->theme_style }}"
                                data-template-preview="{{ $template->preview }}"
                            >Continue</button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Image Preview Modal --}}
<div id="imageModal" class="image-modal">
    <span id="closeModal" class="close-modal">&times;</span>
    <img id="modalImage" src="" alt="Template Preview" class="modal-image">
</div>

<script>
    document.querySelectorAll('.template-img').forEach(img => {
        img.addEventListener('click', function() {
            document.getElementById('modalImage').src = this.src;
            document.getElementById('imageModal').style.display = 'flex';
        });
    });
    document.getElementById('closeModal').onclick = function() {
        document.getElementById('imageModal').style.display = 'none';
        document.getElementById('modalImage').src = '';
    };
    document.getElementById('imageModal').onclick = function(e) {
        if(e.target === this) {
            this.style.display = 'none';
            document.getElementById('modalImage').src = '';
        }
    };
</script>

<style>
    /* Layout and Containers */
    .templates-container {
        max-height: 700px;
        overflow-y: auto;
    }

    .templates-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
        animation: fadeIn 0.8s ease-out;
    }

    /* Template Cards */
    .template-card {
        height: 420px;
        display: flex;
        flex-direction: column;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        transition: box-shadow 0.3s ease, transform 0.3s ease;
        cursor: pointer;
    }

    .template-card:hover,
    .template-card:focus {
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        transform: translateY(-5px);
    }
    

    .template-image-container {
        flex: 0 0 220px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f3f4f6;
        border-radius: 10px 10px 0 0;
    }

    .template-img {
        cursor: pointer;
        max-height: 200px;
        max-width: 90%;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transition: transform 0.3s ease;
    }

    .template-img:hover {
        transform: scale(1.05);
    }

    .card-overlay {
        flex: 1;
        padding: 16px;
        background: rgba(255, 255, 255, 0.95);
        border-radius: 0 0 10px 10px;
        overflow-y: auto;
        transition: background 0.3s ease;
    }

    .card-overlay h3 {
        font-size: 16px;
        font-weight: bold;
        margin-bottom: 8px;
    }

    .card-overlay p {
        font-size: 13px;
        color: #6b7280;
        margin: 0 0 12px 0;
        max-height: 48px;
        overflow: hidden;
    }

    .card-actions {
        margin-top: 12px;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
    }

    /* Modal Styles */
    .image-modal {
        display: none;
        position: fixed;
        z-index: 9999;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.8);
        align-items: center;
        justify-content: center;
        transition: opacity 0.3s ease;
    }

    .close-modal {
        position: absolute;
        top: 30px;
        right: 40px;
        font-size: 2.5rem;
        color: #fff;
        cursor: pointer;
        transition: color 0.3s ease;
    }

    .close-modal:hover {
        color: #ccc;
    }

    .modal-image {
        max-width: 90vw;
        max-height: 90vh;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    }

    /* Button Styles */
    .btn {
        border: none;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.3s ease, transform 0.2s ease;
        text-decoration: none; /* For links */
        display: inline-block; /* Ensure links behave like buttons */
        text-align: center;
    }

    .btn-edit {
        background: #6b7280; /* Neutral gray for edit */
        color: white;
        padding: 8px 16px;
        font-size: 14px;
    }

    .btn-edit:hover {
        background: #4b5563;
        transform: translateY(-1px);
    }

    .btn-delete {
        background: #dc3545; /* Red for delete */
        color: white;
        padding: 8px 16px;
        font-size: 14px;
    }

    .btn-delete:hover {
        background: #c82333;
        transform: translateY(-1px);
    }

    /* Animations */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .templates-grid {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }

        .template-card {
            height: auto;
            min-height: 380px;
        }

        .template-image-container {
            flex: 0 0 180px;
        }

        .modal-image {
            max-width: 95vw;
            max-height: 80vh;
        }
    }
</style>