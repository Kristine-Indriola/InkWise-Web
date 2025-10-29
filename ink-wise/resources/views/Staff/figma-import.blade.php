@extends('layouts.staff')

@section('title', 'Figma Import')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Import from Figma</h1>

            <!-- Figma URL Input -->
            <div class="mb-6">
                <label for="figma_url" class="block text-sm font-medium text-gray-700 mb-2">
                    Figma File URL
                </label>
                <input
                    type="url"
                    id="figma_url"
                    name="figma_url"
                    placeholder="https://www.figma.com/file/AbCdEfGhIjKlMnOpQrSt/Template"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                >
                <p class="text-sm text-gray-500 mt-1">
                    Enter the URL of your Figma file. The system will automatically extract template frames.
                </p>
            </div>

            <!-- Analyze Button -->
            <div class="mb-6">
                <button
                    id="analyze-btn"
                    type="button"
                    class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
                >
                    <span id="analyze-text">Analyze Figma File</span>
                    <span id="analyze-loading" class="hidden">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Analyzing...
                    </span>
                </button>
            </div>

            <!-- Frames Display -->
            <div id="frames-section" class="hidden mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Available Template Frames</h2>
                <div id="frames-list" class="space-y-3">
                    <!-- Frames will be populated here -->
                </div>

                <!-- Import Button -->
                <div class="mt-6">
                    <button
                        id="import-btn"
                        type="button"
                        class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 disabled:opacity-50"
                        disabled
                    >
                        <span id="import-text">Import Selected Templates</span>
                        <span id="import-loading" class="hidden">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Importing...
                        </span>
                    </button>
                </div>
            </div>

            <!-- Results Section -->
            <div id="results-section" class="hidden">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Import Results</h2>
                <div id="results-content"></div>
            </div>

            <!-- Error Messages -->
            <div id="error-message" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <span id="error-text"></span>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const figmaUrlInput = document.getElementById('figma_url');
    const analyzeBtn = document.getElementById('analyze-btn');
    const analyzeText = document.getElementById('analyze-text');
    const analyzeLoading = document.getElementById('analyze-loading');
    const framesSection = document.getElementById('frames-section');
    const framesList = document.getElementById('frames-list');
    const importBtn = document.getElementById('import-btn');
    const importText = document.getElementById('import-text');
    const importLoading = document.getElementById('import-loading');
    const resultsSection = document.getElementById('results-section');
    const resultsContent = document.getElementById('results-content');
    const errorMessage = document.getElementById('error-message');
    const errorText = document.getElementById('error-text');

    let selectedFrames = [];

    // Analyze Figma file
    analyzeBtn.addEventListener('click', async function() {
        const figmaUrl = figmaUrlInput.value.trim();

        if (!figmaUrl) {
            showError('Please enter a Figma URL');
            return;
        }

        // Show loading state
        analyzeBtn.disabled = true;
        analyzeText.classList.add('hidden');
        analyzeLoading.classList.remove('hidden');
        hideError();

        try {
            const response = await fetch('/staff/figma/analyze', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ figma_url: figmaUrl })
            });

            const data = await response.json();

            if (data.success) {
                displayFrames(data.frames, data.file_key);
                framesSection.classList.remove('hidden');
            } else {
                showError(data.message);
            }
        } catch (error) {
            showError('An error occurred while analyzing the Figma file');
            console.error('Figma analysis error:', error);
        } finally {
            // Reset loading state
            analyzeBtn.disabled = false;
            analyzeText.classList.remove('hidden');
            analyzeLoading.classList.add('hidden');
        }
    });

    // Import selected frames
    importBtn.addEventListener('click', async function() {
        if (selectedFrames.length === 0) {
            showError('Please select at least one frame to import');
            return;
        }

        // Show loading state
        importBtn.disabled = true;
        importText.classList.add('hidden');
        importLoading.classList.remove('hidden');
        hideError();

        try {
            const response = await fetch('/staff/figma/import', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    file_key: document.getElementById('file_key').value,
                    frames: selectedFrames,
                    figma_url: figmaUrlInput.value
                })
            });

            const data = await response.json();

            if (data.success) {
                displayResults(data);
                resultsSection.classList.remove('hidden');
            } else {
                showError(data.message);
            }
        } catch (error) {
            showError('An error occurred during import');
            console.error('Figma import error:', error);
        } finally {
            // Reset loading state
            importBtn.disabled = false;
            importText.classList.remove('hidden');
            importLoading.classList.add('hidden');
        }
    });

    function displayFrames(frames, fileKey) {
        framesList.innerHTML = '';

        // Store file key
        const fileKeyInput = document.createElement('input');
        fileKeyInput.type = 'hidden';
        fileKeyInput.id = 'file_key';
        fileKeyInput.value = fileKey;
        framesList.appendChild(fileKeyInput);

        frames.forEach(frame => {
            const frameDiv = document.createElement('div');
            frameDiv.className = 'flex items-center p-4 border border-gray-200 rounded-md';

            frameDiv.innerHTML = `
                <input type="checkbox" class="frame-checkbox mr-3" value="${frame.id}" data-frame='${JSON.stringify(frame)}'>
                <div class="flex-1">
                    <h3 class="font-medium text-gray-900">${frame.name}</h3>
                    <p class="text-sm text-gray-600">Type: ${frame.type}</p>
                </div>
            `;

            framesList.appendChild(frameDiv);
        });

        // Add event listeners to checkboxes
        document.querySelectorAll('.frame-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const frameData = JSON.parse(this.getAttribute('data-frame'));

                if (this.checked) {
                    selectedFrames.push(frameData);
                } else {
                    selectedFrames = selectedFrames.filter(f => f.id !== frameData.id);
                }

                importBtn.disabled = selectedFrames.length === 0;
            });
        });
    }

    function displayResults(data) {
        let html = '<div class="space-y-2">';

        if (data.imported && data.imported.length > 0) {
            html += '<h3 class="font-medium text-green-600">Successfully Imported:</h3>';
            html += '<ul class="list-disc list-inside text-sm text-gray-600">';
            data.imported.forEach(item => {
                html += `<li>${item.name} (${item.type})</li>`;
            });
            html += '</ul>';
        }

        if (data.errors && data.errors.length > 0) {
            html += '<h3 class="font-medium text-red-600 mt-4">Errors:</h3>';
            html += '<ul class="list-disc list-inside text-sm text-red-600">';
            data.errors.forEach(error => {
                html += `<li>${error}</li>`;
            });
            html += '</ul>';
        }

        html += '</div>';
        resultsContent.innerHTML = html;
    }

    function showError(message) {
        errorText.textContent = message;
        errorMessage.classList.remove('hidden');
    }

    function hideError() {
        errorMessage.classList.add('hidden');
    }
});
</script>
@endsection
