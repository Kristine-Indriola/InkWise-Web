<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - Inkwise</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- FontAwesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Seasons&family=Montserrat:wght@400;500;700&display=swap');
        @import url('https://fonts.cdnfonts.com/css/edwardian-script-itc');
        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;700&display=swap');

        :root {
            --color-primary: #06b6d4;
            --color-primary-dark: #0891b2;
            --shadow-elevated: 0 16px 48px rgba(4, 29, 66, 0.18);
            --font-display: 'Playfair Display', serif;
            --font-accent: 'Seasons', serif;
            --font-script: 'Edwardian Script ITC', cursive;
            --font-body: 'Montserrat', 'Helvetica Neue', Arial, sans-serif;
        }

        .layout-container {
            width: min(1200px, 100%);
            margin-inline: auto;
            padding-inline: clamp(24px, 5vw, 32px);
        }

        .search-results {
            padding: 2rem 0;
        }

        .search-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .search-title {
            font-family: var(--font-display);
            font-size: 2.5rem;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .search-query {
            font-size: 1.1rem;
            color: #6b7280;
        }

        .templates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .template-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .template-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .template-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .template-content {
            padding: 1.5rem;
        }

        .template-title {
            font-family: var(--font-display);
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .template-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 1rem;
        }

        .template-description {
            color: #4b5563;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .no-results {
            text-align: center;
            padding: 4rem 2rem;
        }

        .no-results-icon {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }

        .no-results-title {
            font-family: var(--font-display);
            font-size: 1.875rem;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .no-results-text {
            color: #6b7280;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>

    <!-- Include the topbar from dashboard -->
    @include('customer.dashboard', ['includeTopbarOnly' => true])

    <main class="search-results">
        <div class="layout-container">
            <div class="search-header">
                <h1 class="search-title">Search Results</h1>
                @if($query)
                    <p class="search-query">Showing results for "<strong>{{ $query }}</strong>"</p>
                @else
                    <p class="search-query">Please enter a search term</p>
                @endif
            </div>

            @if($templates->count() > 0)
                <div class="templates-grid">
                    @foreach($templates as $template)
                        <div class="template-card">
                            @if($template->preview_front)
                                <img src="{{ asset('storage/' . $template->preview_front) }}"
                                     alt="{{ $template->name }}"
                                     class="template-image"
                                     onerror="this.src='{{ asset('images/placeholder-template.jpg') }}'">
                            @else
                                <div class="template-image" style="background: linear-gradient(135deg, #f3f4f6, #e5e7eb); display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-image text-4xl text-gray-400"></i>
                                </div>
                            @endif

                            <div class="template-content">
                                <h3 class="template-title">{{ $template->name }}</h3>
                                <div class="template-meta">
                                    @if($template->event_type)
                                        <span><i class="fas fa-calendar-alt"></i> {{ ucfirst($template->event_type) }}</span>
                                    @endif
                                    @if($template->product_type)
                                        <span><i class="fas fa-tag"></i> {{ ucfirst($template->product_type) }}</span>
                                    @endif
                                </div>
                                @if($template->description)
                                    <p class="template-description">{{ Str::limit($template->description, 100) }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                {{ $templates->appends(['query' => $query])->links() }}
            @else
                @if($query)
                    <div class="no-results">
                        <div class="no-results-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h2 class="no-results-title">No templates found</h2>
                        <p class="no-results-text">We couldn't find any templates matching "{{ $query }}". Try different keywords or browse our categories.</p>
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-[#06b6d4] text-white rounded-lg hover:bg-[#0891b2] transition-colors">
                            <i class="fas fa-arrow-left"></i>
                            Back to Home
                        </a>
                    </div>
                @else
                    <div class="no-results">
                        <div class="no-results-icon">
                            <i class="fas fa-keyboard"></i>
                        </div>
                        <h2 class="no-results-title">Start your search</h2>
                        <p class="no-results-text">Enter a search term above to find templates, themes, or event types.</p>
                    </div>
                @endif
            @endif
        </div>
    </main>

</body>
</html>
