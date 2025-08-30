@extends('layouts.admin')

@section('title', 'Template Editor')

@section('content')
<div class="container">
    <h2 class="mb-3">Template Editor</h2>
    <input type="text" id="templateName" class="form-control mb-3" 
           placeholder="Template Name" value="{{ $template->name ?? '' }}">

    <div class="row">
        <div class="col-md-3">
            <h5>Tools</h5>
            <button class="btn btn-sm btn-primary mb-2" onclick="addText()">Add Text</button>
            <button class="btn btn-sm btn-success mb-2" onclick="addImage()">Add Image</button>
            <button class="btn btn-sm btn-danger mb-2" onclick="deleteSelected()">Delete</button>

            <h6 class="mt-3">Font Options</h6>
            <select id="fontSelect" class="form-control" onchange="changeFont(this.value)">
                <option value="Arial">Arial</option>
                <option value="Times New Roman">Times New Roman</option>
                <option value="Courier New">Courier New</option>
                <option value="Georgia">Georgia</option>
            </select>
            <input type="color" id="colorPicker" class="form-control mt-2" onchange="changeColor(this.value)">
        </div>

        <div class="col-md-9">
            <canvas id="canvas" width="800" height="600" style="border:1px solid #ddd;"></canvas>
        </div>
    </div>

    <button class="btn btn-success mt-3" onclick="saveTemplate()">Save Template</button>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.2.4/fabric.min.js"></script>
<script>
    const canvas = new fabric.Canvas('canvas');

    // Load saved design if editing
    @if($template && $template->design)
        canvas.loadFromJSON({!! json_encode($template->design) !!}, canvas.renderAll.bind(canvas));
    @endif

    function addText() {
        const text = new fabric.Textbox("Sample Text", {
            left: 100, top: 100, fontSize: 24, fill: '#000000'
        });
        canvas.add(text).setActiveObject(text);
    }

    function addImage() {
        const url = prompt("Enter Image URL:");
        if(url){
            fabric.Image.fromURL(url, function(img) {
                img.scaleToWidth(200);
                canvas.add(img).setActiveObject(img);
            });
        }
    }

    function deleteSelected() {
        const active = canvas.getActiveObject();
        if (active) canvas.remove(active);
    }

    function changeFont(font) {
        const active = canvas.getActiveObject();
        if (active && active.type === "textbox") {
            active.set("fontFamily", font);
            canvas.requestRenderAll();
        }
    }

    function changeColor(color) {
        const active = canvas.getActiveObject();
        if (active && active.type === "textbox") {
            active.set("fill", color);
            canvas.requestRenderAll();
        }
    }

    function saveTemplate() {
        const json = JSON.stringify(canvas.toJSON());
        const name = document.getElementById("templateName").value;

        fetch("{{ route('admin.templates.store') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                id: {{ $template->id ?? 'null' }},
                name: name,
                design: json
            })
        })
        .then(res => res.json())
        .then(data => {
            alert("Template saved!");
            if(data.id){
                window.location.href = "/admin/templates/editor/" + data.id;
            }
        });
    }
</script>
@endsection
