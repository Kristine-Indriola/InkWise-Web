<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Design - Inkwise</title>
</head>
   <link rel="stylesheet" href="{{ asset('css/customer/editing.css') }}">
   <script src="{{ asset('js/customer/editing.js') }}" defer></script>
   
   
<body>
    <!-- TOP BAR -->
    <div class="topbar">
        <div class="left-tools">
            <button class="save-btn">Save</button>
            <button class="undo-btn">↶ Undo</button>
            <button class="redo-btn">↷ Redo</button>
        </div>
        <div class="right-tools">
            <button class="change-template">Change template</button>
            <button class="preview-btn">Preview</button>
            <a href="{{ route('order.form') }}" class="next-btn">Next</a>
        </div>
    </div>

    <div class="editor-container">
        <!-- LEFT SIDEBAR -->
        <div class="sidebar">
            <button class="side-btn active">Text</button>
            <button class="side-btn">Images</button>
            <button class="side-btn">Graphics</button>
            <button class="side-btn">Tables</button>
            <button class="side-btn">Design color</button>
        </div>

        <!-- MIDDLE CANVAS -->
        <div class="canvas-area">
            <div class="canvas">
                <div class="safety-area">Safety Area</div>
                <div class="bleed-line">Bleed</div>
                <img id="cardFront" src="{{ asset('customerimages/invite/wedding3.jpg') }}" class="card active" alt="Front Design">
                <img id="cardBack" src="{{ asset('customerimages/invite/wed1.png') }}" class="card" alt="Back Design">
            </div>
            <div class="zoom-controls">
                <button id="zoomOut">-</button>
                <span id="zoomLevel">100%</span>
                <button id="zoomIn">+</button>
            </div>
        </div>

        <!-- RIGHT PANEL (Front/Back toggle + text fields) -->
        <div class="right-panel">
            <div class="view-toggle">
                <button id="showFront" class="active">Front</button>
                <button id="showBack">Back</button>
            </div>

            <div class="text-editor">
                <h3>Text</h3>
                <div id="textFields">
                    <div class="text-field">
                        <input type="text" value="06.28.26">
                        <button class="delete-text">🗑</button>
                    </div>
                    <div class="text-field">
                        <input type="text" value="SAVE">
                        <button class="delete-text">🗑</button>
                    </div>
                    <div class="text-field">
                        <input type="text" value="DATE">
                        <button class="delete-text">🗑</button>
                    </div>
                    <div class="text-field">
                        <input type="text" value="KENDRA AND ANDREW">
                        <button class="delete-text">🗑</button>
                    </div>
                    <div class="text-field">
                        <input type="text" value="BROOKLYN, NY">
                        <button class="delete-text">🗑</button>
                    </div>
                </div>
                <button id="addTextField" class="add-btn">+ New Text Field</button>
            </div>
        </div>
    </div>

    
</body>
</html>
