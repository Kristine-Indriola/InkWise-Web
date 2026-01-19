<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inkwise Dashboard</title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>

    <!-- FontAwesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

   <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Seasons&family=Montserrat:wght@400;500;700&display=swap');
        @import url('https://fonts.cdnfonts.com/css/edwardian-script-itc');
        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;700&display=swap');
        @import url('https://fonts.cdnfonts.com/css/bugaki');
        @import url('https://fonts.cdnfonts.com/css/garet');
        @import url('https://fonts.googleapis.com/css2?family=Forum&display=swap');
        <!-- Ensure Cormorant loads early -->
        <link rel="preload" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;700&display=swap" as="style">

        :root {
            --color-primary: #06b6d4;
            --color-primary-dark: #0891b2;
            --shadow-elevated: 0 16px 48px rgba(4, 29, 66, 0.18);
            --font-display: 'Playfair Display', serif;
            --font-accent: 'Seasons', serif;
            --font-script: 'Edwardian Script ITC', cursive;
            --font-body: 'Montserrat', 'Helvetica Neue', Arial, sans-serif;
            --font-montserrat: 'Montserrat', 'Helvetica Neue', Arial, sans-serif;
            --font-garet: 'Garet', 'Garet Display', sans-serif;
            --font-bugaki: 'Bugaki', cursive;
            --font-cormorant: 'Cormorant Garamond', 'Cormorant', serif;
        }

        .layout-container {
            width: min(1200px, 100%);
            margin-inline: auto;
            padding-inline: clamp(24px, 5vw, 32px);
        }

        /* Topbar and hero base */
        .topbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 60;
            background: rgba(248, 246, 235, 0.92);
            backdrop-filter: blur(18px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.65);
        }

        .topbar-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: min(1200px, 100%);
            margin: 0 auto;
            padding-inline: clamp(20px, 4vw, 32px);
            padding-block: 0.7rem;
            gap: 1.5rem;
        }

        .topbar-brand {
            display: flex;
            align-items: baseline;
            gap: 0.25rem;
            font-family: var(--font-bugaki);
            text-transform: uppercase;
            font-size: 1.25rem;
            /* entire wordmark: no extra letter spacing */
            letter-spacing: 0;
            /* balanced vertical alignment for the wordmark */
            line-height: 1.2;
            color: #0b0b0b;
            margin-left: -15rem; /* move logo further to the left */
        }

        /* Logo: the initial 'I' */
        .logo-i {
            /* Use Bugaki for the capital I as requested */
            font-family: var(--font-bugaki) !important;
            color: #000000 !important;
            /* very bold Roman-numeral appearance */
            font-weight: 1000 !important; /* may fallback to heaviest available */
            font-size: 2.6rem !important; /* slightly reduced size */
            display: inline-block;
            /* ensure it's fully static */
            animation: none !important;
            -webkit-animation: none !important;
            transition: none !important;
            line-height: 1.2; /* match the wordmark */
            letter-spacing: 0;
            /* Stronger stroke and layered shadows for thickness */
            -webkit-text-stroke: 3px #000; /* thicker WebKit stroke */
            text-stroke: 3px #000; /* fallback */
            text-shadow:
                0 1px 0 rgba(0,0,0,0.18),
                1px 0 0 rgba(0,0,0,0.16),
               -1px 0 0 rgba(0,0,0,0.16),
                0 2px 0 rgba(0,0,0,0.08),
                0 3px 0 rgba(0,0,0,0.04);
        }

        /* Ensure any script-specific rules don't override the Times styling */
        .logo-i.logo-script {
            font-family: 'Times New Roman', Times, serif !important;
            font-style: normal !important;
            font-weight: 700 !important;
        }

        .topbar-brand .logo-serif {
            font-family: var(--font-garet);
            font-size: 1rem;
            letter-spacing: 0; /* characters sit tightly together */
            line-height: 1.2; /* balanced vertical alignment */
            text-transform: uppercase; /* caps lock */
            color: inherit;
            font-weight: 400; /* not bold */
        }

        .topbar-nav {
            display: flex;
            gap: clamp(0.75rem, 2vw, 1.5rem);
            align-items: center;
            justify-content: center;
            flex: 1;
            /* push nav links further toward the right side of the topbar (overlap slightly) */
            margin-left: auto;
            margin-right: -8rem;
            font-family: var(--font-garet);
            font-size: clamp(0.9rem, 1vw, 1.05rem);
            text-transform: uppercase;
        }

        #mainNav a {
            color: #1f2937;
            padding: 0.4rem 0.85rem;
            border-radius: 999px;
            transition: color 0.2s ease, background 0.2s ease, transform 0.2s ease;
        }

        #mainNav a:hover {
            color: #0f172a;
            background: rgba(255, 255, 255, 0.6);
            transform: translateY(-1px);
        }

        #mainNav .topbar-signup {
            background: #0f172a;
            color: #fff;
            padding: 0.55rem 1.35rem;
            font-weight: 600;
            border-radius: 999px;
        }

        #mainNav .topbar-signup:hover {
            background: #1f2937;
        }

        #mainNav.mobile-open {
            display: flex !important;
            flex-direction: column;
            gap: 0.35rem;
            margin-top: 0.75rem;
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.14);
        }

        /* Hero section styling */
        .hero-section {
            position: relative;
            /* reduce top gap so the hero content sits higher on the page */
            padding-top: clamp(60px, 8vw, 90px);
            padding-bottom: clamp(120px, 12vw, 160px);
            /* keep a tall hero but allow the visual to start nearer the top */
            min-height: calc(110vh - 80px);
            background-color: #f8f6eb;
            background-image: url(<?php echo e(asset('customerVideo/Video/wed.jpg')); ?>);
            background-size: cover;
            /* anchor the background higher so image content moves up */
            background-position: center top;
            background-repeat: no-repeat;
            overflow: hidden;
        }

        .hero-section::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(248, 246, 235, 0.7), rgba(248, 246, 235, 0));
            z-index: 1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: min(780px, 100%);
            display: flex;
            flex-direction: column;
            gap: 1rem;
            text-align: left;
            /* shift the text content to the right */
            margin-left: 7rem;
        }

        /* Two-column hero layout: visual (left) + content (right) */
        .hero-inner {
            display: flex;
            align-items: center;
            gap: clamp(1rem, 4vw, 3rem);
            max-width: 1200px;
            margin: 0 auto;
            padding-inline: clamp(20px, 4vw, 32px);
            position: relative;
            z-index: 2;
        }

        .hero-visual {
            flex: 0 0 clamp(320px, 45%, 520px);
            display: flex;
            align-items: center;
            justify-content: center;
            /* move the visual down */
            margin-top: 2rem;
            /* move a little to the left */
            margin-left: -9rem;
        }

        .hero-frame {
            width: 100%;
            aspect-ratio: 3/4;
            overflow: hidden;
            display: block;
            position: relative;
        }

        .hero-frame-img,
        .hero-frame-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            -webkit-mask-image: url('<?php echo e(asset('customerVideo/Video/shape.gif')); ?>');
            -webkit-mask-repeat: no-repeat;
            -webkit-mask-position: center;
            -webkit-mask-size: cover;
            mask-image: url('<?php echo e(asset('customerVideo/Video/shape.gif')); ?>');
            mask-repeat: no-repeat;
            mask-position: center;
            mask-size: cover;
        }

        @media (max-width: 900px) {
            .hero-inner { flex-direction: column-reverse; text-align: center; }
            .hero-visual { margin-bottom: 1rem; }
            .hero-content { max-width: 100%; }
        }

        .hero-title {
            display: flex;
            flex-direction: row; /* place the two words side-by-side */
            gap: 0.75rem;
            align-items: flex-end;
            font-size: clamp(2.7rem, 6vw, 4.5rem);
            line-height: 1.05;
            text-transform: uppercase;
            font-weight: 800;
        }

        /* Render the two words vertically (letters stacked top->bottom).
           Use vertical writing mode with upright text orientation so letters remain readable.
           Also enforce non-bold weight and 15px size as requested. */
        .hero-title-invitation,
        .hero-title-maker {
            display: inline-block;
            writing-mode: horizontal-tb;
            text-orientation: mixed;
            font-weight: 400; /* not bold */
            line-height: 1;
            -webkit-font-smoothing: antialiased;
            text-transform: uppercase;
            margin: 0;
        }

        .hero-title-invitation { font-family: var(--font-montserrat); font-size: 80px; letter-spacing: 10px; line-height: 1.2; }
        .hero-title-maker {
            font-family: 'Times New Roman MT Condensed', 'Times New Roman', serif;
            color: #545454;
            letter-spacing: 0.12em;
            font-size: 60px;
            font-style: italic;
            line-height: 1.2;
        }

        .hero-tagline {
            font-family: var(--font-cormorant);
            font-size: clamp(1.15rem, 2vw, 1.5rem);
            color: #1f2937;
            max-width: 600px;
            line-height: 1.4;
            text-align: center;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: flex-end;
        }

        .hero-btn {
            font-family: var(--font-cormorant);
            padding: 0.95rem 1.9rem;
            border-radius: 999px;
            font-size: 1rem;
            transition: transform 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 160px;
            text-align: center;
        }

        .hero-btn--primary {
            background: #111111;
            color: #ffffff;
            border: 2px solid #111111;
        }

        .hero-btn--ghost {
            background: rgba(255, 255, 255, 0.85);
            color: #111111;
            border: 2px solid rgba(0, 0, 0, 0.1);
            box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.08);
        }

        .hero-btn:hover {
            transform: translateY(-2px) scale(1.01);
        }

        .hero-btn--ghost:hover {
            background: #ffffff;
            border-color: #111111;
        }

        @media (max-width: 768px) {
            .topbar-inner {
                flex-wrap: wrap;
            }

            .hero-title {
                letter-spacing: 0.15em;
            }

            #mainNav {
                display: none;
            }

            #mainNav.mobile-open {
                margin-top: 0.6rem;
            }
        }

        /* Full-page background video (behind main content) */
        .page-bg-video {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 0;
            pointer-events: none;
            opacity: 0.85;
            filter: saturate(1.05) contrast(1.02);
        }

        /* Categories section background video */
        .categories-section { position: relative; overflow: hidden; }
        .section-with-media {
            position: relative;
            overflow: hidden;
            background-color: #f8f6eb;
        }
        .section-base {
            position: relative;
            background-color: #f8f6eb;
        }
        .section-bg-media {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 0;
            pointer-events: none;
            filter: brightness(0.86);
        }
        .categories-content { position: relative; z-index: 10; }

        /* Generic section background video styles (usable by about/contact/categories) */
        .section-bg-video {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 0;
            pointer-events: none;
            opacity: 0.45;
            transform: scale(1.06);
            filter: brightness(0.9) saturate(0.95);
        }
        .section-content { position: relative; z-index: 10; }
        .section-bg-overlay { position: absolute; inset: 0; z-index: 5; background: linear-gradient(rgba(255,255,255,0.18), rgba(255,255,255,0.22)); pointer-events: none; }

        /* Hide heavy background video on small screens */
        @media (max-width: 767px) {
            .categories-bg-video, .section-bg-video { display: none; }
        }

        /* Make card images appear transparent and sharp */
        .transparent-card-img {
            background: transparent !important;
            padding: 0 !important;
            object-fit: contain !important;
            image-rendering: -webkit-optimize-contrast;
            image-rendering: crisp-edges;
            -webkit-backface-visibility: hidden;
        }

        /* Ensure dashboard hero sits above the dashboard background video */
        body#dashboard main {
            position: relative;
            overflow: visible;
        }
        body#dashboard main > .section-bg-video {
            z-index: 0;
            opacity: 0.32; /* slightly dim for readability */
        }
        /* Coco Gothic labels for category headings */
        .coco-gothic {
            font-family: 'Coco Gothic', 'CocoGothic', system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
            letter-spacing: 0.02em;
            font-weight: 700 !important;
        }
        /* Uppercase only the main Categories title */
        .categories-title { text-transform: uppercase; }

        /* Cormorant Garamond for descriptive text */
        .cormorant {
            font-family: 'Cormorant Garamond', serif !important;
            font-weight: 400 !important;
            color: #475569 !important; /* slightly muted slate for readability */
        }

        .layout-stack {
            display: flex;
            flex-direction: column;
            gap: clamp(40px, 6vw, 72px);
        }

        body {
            font-family: var(--font-body);
            color: #1f2937;
            background-color: #f8f6eb;
        }

        h1, h2, h3, h4 {
            font-family: var(--font-display);
        }

        a {
            transition: color .2s ease, transform .2s ease;
        }

        /* About section redesign */
        .about-section {
            position: relative;
        }

        .about-elevated {
            display: grid;
            grid-template-columns: minmax(280px, 1fr) minmax(320px, 1.2fr);
            gap: clamp(2rem, 6vw, 4rem);
            align-items: center;
        }

        .about-left {
            position: relative;
        }

        .about-image-wrapper {
            position: relative;
            display: grid;
            place-items: center;
            padding: clamp(1.5rem, 5vw, 3rem);
            background: radial-gradient(circle at top, rgba(255, 192, 203, 0.45), rgba(252, 157, 155, 0.25));
            border-radius: 32px;
        }

        .about-invite-badge {
            position: absolute;
            top: clamp(-18px, -4vw, -24px);
            left: 50%;
            transform: translateX(-50%);
            background: #fdf1f3;
            border: 1px solid rgba(255, 182, 193, 0.6);
            color: #cc527a;
            font-family: var(--font-garet);
            text-transform: uppercase;
            letter-spacing: 0.2em;
            font-size: 0.7rem;
            padding: 0.55rem 1.4rem;
            border-radius: 999px;
            box-shadow: 0 12px 24px rgba(204, 82, 122, 0.12);
        }

        .about-image-arch {
            width: clamp(220px, 80%, 320px);
            aspect-ratio: 3 / 4;
            border-radius: 160px 160px 60px 60px;
            overflow: hidden;
            position: relative;
            box-shadow:
                0 20px 45px rgba(204, 82, 122, 0.22),
                0 20px 40px rgba(0, 0, 0, 0.08);
        }

        .about-image-arch img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .about-title-overlay {
            position: absolute;
            bottom: 1.5rem;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(255, 255, 255, 0.9);
            padding: 0.6rem 1.5rem;
            border-radius: 999px;
            font-family: var(--font-cormorant);
            font-size: clamp(1rem, 2vw, 1.25rem);
            color: #8d3c65;
            letter-spacing: 0.08em;
            box-shadow: 0 14px 28px rgba(141, 60, 101, 0.18);
        }

        .about-line-accent {
            position: absolute;
            bottom: -30px;
            right: clamp(12px, 4vw, 32px);
            width: clamp(80px, 20vw, 130px);
            height: 2px;
            background: linear-gradient(90deg, rgba(255, 192, 203, 0), rgba(204, 82, 122, 0.65));
        }

        .about-circle-accent {
            position: absolute;
            bottom: -50px;
            left: clamp(15px, 3vw, 42px);
            width: clamp(50px, 15vw, 70px);
            height: clamp(50px, 15vw, 70px);
            border-radius: 50%;
            background: radial-gradient(circle at center, rgba(255, 192, 203, 0.6), rgba(252, 157, 155, 0.35));
            opacity: 0.85;
        }

        .about-right {
            position: relative;
            background: #fefcf5;
            border-radius: 36px;
            padding: clamp(2rem, 5vw, 3.5rem);
            box-shadow: 0 25px 60px rgba(69, 56, 32, 0.12);
        }

        .about-content {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            text-align: left;
        }

        .about-heading {
            font-family: 'Forum', serif;
            font-size: clamp(2.2rem, 4vw, 3rem);
            text-transform: uppercase;
            letter-spacing: 0.2em;
            color: #000000; /* changed to black */
            display: flex;
            flex-direction: row; /* show "About" and "Us" side-by-side */
            gap: 0.75rem;
            align-items: center;
        }

        .about-heading span {
            font-size: clamp(1.5rem, 3vw, 2rem);
            letter-spacing: 0.35em;
            color: #000000; /* changed to black */
            margin-right: 0.25rem;
        }

        .about-subheading {
            font-family: var(--font-garet);
            text-transform: uppercase;
            letter-spacing: 0.45em;
            font-size: 0.85rem;
            color: rgba(47, 42, 38, 0.7);
        }

        .about-body {
            font-family: var(--font-montserrat);
            font-size: clamp(1rem, 2vw, 1.05rem);
            line-height: 1.8;
            color: rgba(47, 42, 38, 0.78);
        }

        @media (max-width: 900px) {
            .about-elevated {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .about-left {
                order: -1;
                display: grid;
                place-items: center;
            }

            .about-right {
                margin-top: clamp(1rem, 4vw, 2rem);
            }

            .about-content {
                text-align: center;
                align-items: center;
            }

            .about-heading {
                align-items: center;
            }
        }

        .logo-i {
            line-height: 1;
            font-family: var(--font-bugaki) !important;
            color: #000000 !important;
            animation: none !important;
            transform: none !important;
        }

        .logo-script {
            font-family: var(--font-script);
            color: var(--color-primary);
        }

        .logo-serif {
            font-family: var(--font-garet);
            color: var(--color-primary-dark);
            letter-spacing: 0;
            text-transform: uppercase;
            line-height: 0.9;
        }

        .page-title {
            font-size: 1.1rem;
        }

        .btn-primary {
            background: var(--color-primary);
            color: #ffffff;
            font-family: var(--font-display);
        }

        .btn-outline {
            border: 2px solid transparent;
            background-origin: border-box;
            background-clip: padding-box, border-box;
            background-image: linear-gradient(#ffffff, #ffffff), linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
            color: #1f2937;
            font-family: var(--font-display);
        }

        .btn-outline:hover {
            color: var(--color-primary-dark);
        }

        .focus-ring:focus {
            outline: 3px solid rgba(6, 182, 212, 0.25);
            outline-offset: 2px;
        }

        .template-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .space-y-6 {
            gap: .75rem;
        }

        .text-5xl {
            font-size: 2.25rem;
        }

        .text-lg {
            font-size: 0.95rem;
        }

        .chat-widget {
            position: fixed;
            right: 1.25rem;
            bottom: 1.25rem;
            z-index: 60;
        }

        .chat-btn {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            padding: 6px;
            background: linear-gradient(90deg, #5de0e6, #004aad);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 26px rgba(4, 29, 66, 0.14);
            cursor: pointer;
            transition: transform .15s ease, box-shadow .15s ease;
        }

        .chat-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 30px rgba(4, 29, 66, 0.18);
        }

        .chat-btn:active {
            transform: scale(.98);
        }

        .chat-inner {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .chat-inner img {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 50%;
        }

        .chat-panel {
            width: 560px;
            max-width: calc(100vw - 3rem);
            position: absolute;
            right: 0;
            bottom: 100px;
            background: #ffffff;
            border-radius: 14px;
            box-shadow: var(--shadow-elevated);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(0, 0, 0, 0.04);
        }

        /* icon placement for header */
        .nav-icon-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.35rem;
            height: 1.35rem;
            border-radius: 9999px;
            border: none;
            background: transparent;
            color: #f472b6;
            transition: transform 0.2s ease, color 0.2s ease;
            box-shadow: none;
            position: relative;
        }

        .nav-icon-button:hover {
            transform: translateY(-1px);
            color: #e11d48;
        }

        .nav-icon-button i {
            font-size: 0.75rem;
        }

        .notification-badge {
            position: absolute;
            top: -3px;
            right: -3px;
            background: #ef4444;
            color: white;
            border-radius: 9999px;
            padding: 1px 4px;
            font-size: 9px;
            font-weight: 700;
            min-width: 16px;
            text-align: center;
        }

        .chat-header {
            padding: 16px 18px;
            display: flex;
            gap: 12px;
            align-items: center;
            border-bottom: 1px solid rgba(0, 0, 0, 0.04);
        }

        .chat-header h4 {
            margin: 0;
            font-weight: 800;
            font-size: 16px;
            color: #044e86;
        }

        .chat-body {
            padding: 16px 16px 18px;
            max-height: 520px;
            overflow: auto;
            display: flex;
            flex-direction: column;
            gap: 14px;
            scroll-behavior: smooth;
            background: linear-gradient(180deg, rgba(6, 182, 212, 0.02), transparent);
        }

        .chat-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            overflow: hidden;
        }

        .chat-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .chat-body::-webkit-scrollbar {
            width: 10px;
        }

        .chat-body::-webkit-scrollbar-track {
            background: transparent;
        }

        .chat-body::-webkit-scrollbar-thumb {
            background: rgba(4, 29, 66, 0.1);
            border-radius: 9999px;
        }

        .chat-input {
            display: flex;
            gap: 12px;
            padding: 14px;
            border-top: 1px solid rgba(0, 0, 0, 0.04);
        }

        .chat-input input[type="text"] {
                    /* Ensure template images scale nicely */
                .template-image { width: 100%; height: 100%; object-fit: cover; }
            position: relative;
            max-width: 86%;
            padding: 12px 14px;
            border-radius: 16px;
            font-size: 15px;
            line-height: 1.4;
            word-break: break-word;
            box-shadow: 0 8px 22px rgba(4, 29, 66, 0.05);
        }

        .msg .avatar {
            width: 44px;
            height: 44px;
            border-radius: 9999px;
            overflow: hidden;
            flex-shrink: 0;
            box-shadow: 0 3px 8px rgba(4, 29, 66, 0.06);
        }

        .msg .bubble {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 48px;
        }

        .msg .text {
            white-space: pre-wrap;
        }

        .msg .time {
            font-size: 12px;
            color: #6b7280;
            align-self: flex-end;
            margin-top: 6px;
        }

        .msg.user {
            background: linear-gradient(180deg, #e6f7fb, #c9f0f5);
            margin-left: auto;
            align-self: flex-end;
            color: #022a37;
            border-bottom-right-radius: 6px;
        }

        .msg.bot {
            background: linear-gradient(180deg, #f4f8ff, #eaf3ff);
            align-self: flex-start;
            color: #03305a;
            border-bottom-left-radius: 6px;
            gap: 12px;
            align-items: flex-start;
        }

        .msg.bot::after,
        .msg.user::after {
            content: "";
            position: absolute;
            top: 16px;
            width: 14px;
            height: 14px;
            transform: rotate(45deg);
            box-shadow: 0 8px 14px rgba(4, 29, 66, 0.03);
            border-radius: 2px;
            z-index: 0;
            background: inherit;
        }

        .msg.bot::after {
            left: -7px;
        }

        .msg.user::after {
            right: -7px;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            margin-top: 0.5rem;
            width: 12rem;
            background: #ffffff;
            border-radius: 0.75rem;
            box-shadow: 0 16px 32px rgba(2, 6, 23, 0.16);
            border: 1px solid rgba(0, 0, 0, 0.04);
            overflow: hidden;
            transform: translateY(0.5rem);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease, transform 0.2s ease;
            z-index: 50;
        }

        .dropdown-menu.is-open {
            display: block;
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        .dropdown-menu a,
        .dropdown-menu button,
        .dropdown-menu div {
            display: block;
            width: 100%;
            padding: 0.5rem 1rem;
            font-size: 0.95rem;
            color: #374151;
            background: transparent;
            text-align: left;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .dropdown-menu a:hover,
        .dropdown-menu button:hover,
        .dropdown-menu div:hover {
            background: #e0f7fa;
            color: #065f73;
        }

        #bgCanvas {
            display: block;
            background: linear-gradient(180deg, #ffffff, #fbfdff);
        }

        #mainNav.mobile-open {
            display: flex !important;
        }

        #mainNav {
            z-index: 40;
            position: relative;
        }

        @media (max-width: 767px) {
            #mainNav {
                display: none;
                position: relative;
                width: 100%;
            }

            .chat-btn {
                width: 70px;
                height: 70px;
            }

            .chat-inner img,
            .chat-avatar {
                width: 38px;
                height: 38px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
        }
   </style>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Icon fonts used by invitations/header -->
    <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/uicons-bold-rounded/css/uicons-bold-rounded.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <?php if(request('modal') === 'login'): ?>
        <script>
            window.__OPEN_MODAL__ = 'login';
        </script>
    <?php endif; ?>

    <!-- Custom CSS -->

    <link rel="stylesheet" href="<?php echo e(asset('css/customer/customer.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('css/customer/customertemplate.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('css/customer/template.css')); ?>">

    <!-- Custom JS -->
    <script src="<?php echo e(asset('js/customer/customer.js')); ?>" defer></script>
    <script src="<?php echo e(asset('js/customer/customertemplate.js')); ?>" defer></script>
    <script src="<?php echo e(asset('js/customer/template.js')); ?>" defer></script>

    <!-- Alpine.js for interactivity -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.10.2/cdn.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script&display=swap" rel="stylesheet">
      <link rel="icon" type="image/png" href="<?php echo e(asset('adminimage/ink.png')); ?>">
    <!-- Chat widget styles -->
    <style>
        /* Responsive tweaks for dashboard */
        .logo-i { line-height: 1; }
        .page-title { font-size: 1.1rem; }

        /* Ensure template images scale nicely */
        .template-image { width: 100%; height: 100%; object-fit: cover; }

    /* Hero spacing tweaks */
    .space-y-6 { gap: .75rem; }
    .text-5xl { font-size: 2.25rem; }
    .text-lg { font-size: 0.95rem; }

        /* Accessible focus styles */
        .focus-ring:focus { outline: 3px solid rgba(6,182,212,0.25); outline-offset: 2px; }

        /* Chat widget container (fixed bottom-right) */
        .chat-widget { position: fixed; right: 1.25rem; bottom: 1.25rem; z-index: 60; }

        /* Circular button with 90deg linear-gradient stroke (enlarged) */
       .chat-btn {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    padding: 6px;
    background: linear-gradient(90deg, #5de0e6, #004aad);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 26px rgba(4, 29, 66, 0.14);
    cursor: pointer;
    transition: transform .15s ease;
}
        .chat-btn:active { transform: scale(.98); }

        /* inner circle that holds the image */
       .chat-inner {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
       .chat-inner img {
    width: 45px;   /* fixed size */
    height: 45px;
    object-fit: cover;
    border-radius: 50%;
}

        /* chat panel (enlarged for readability) */
      .chat-panel {
    width: 560px;
    max-width: calc(100vw - 3rem);
    position: absolute;
    right: 0;
    bottom: 100px;
    background: #ffffff;
    border-radius: 14px;
    box-shadow: 0 16px 48px rgba(4,29,66,0.18);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    border: 1px solid rgba(0,0,0,0.04);
}
        .chat-header { padding: 16px 18px; display:flex; gap:12px; align-items:center; border-bottom: 1px solid rgba(0,0,0,0.04); }
        .chat-header h4 { margin:0; font-weight:800; font-size:16px; color:#044e86; }
        .chat-body {
            padding:16px;
            padding-bottom: 18px;
            max-height:520px;
            overflow:auto;
            display:flex;
            flex-direction:column;
            gap:14px;
            scroll-behavior:smooth;
            background: linear-gradient(180deg, rgba(6,182,212,0.02), transparent);
        }

        .chat-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    overflow: hidden;
}
.chat-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}





        /* custom thin scrollbar */
        .chat-body::-webkit-scrollbar { width: 10px; }
        .chat-body::-webkit-scrollbar-track { background: transparent; }
        .chat-body::-webkit-scrollbar-thumb { background: rgba(4,29,66,0.10); border-radius: 9999px; }

        .chat-input { display:flex; gap:12px; padding:14px; border-top:1px solid rgba(0,0,0,0.04); }
        .chat-input input[type="text"]{ flex:1; border-radius:999px; padding:12px 18px; border:1px solid rgba(0,0,0,0.08); outline:none; background:#fbfeff; font-size:15px; }
        .chat-input button{ background:#06b6d4; color:#fff; border-radius:999px; padding:10px 16px; border:0; cursor:pointer; font-weight:700; }

        /* message bubbles (larger) */
        .msg { display:inline-flex; position:relative; max-width:86%; padding:12px 14px; border-radius:16px; font-size:15px; line-height:1.4; word-break:break-word; box-shadow: 0 8px 22px rgba(4,29,66,0.05); }
        .msg .avatar { width:44px; height:44px; border-radius:9999px; overflow:hidden; flex-shrink:0; box-shadow:0 3px 8px rgba(4,29,66,0.06); }
        .msg .bubble { display:flex; flex-direction:column; gap:8px; min-width:48px; }
        .msg .text { white-space:pre-wrap; }
        .msg .time { font-size:12px; color:#6b7280; align-self:flex-end; margin-top:6px; }

        .msg.user {
            background: linear-gradient(180deg,#e6f7fb,#c9f0f5);
            margin-left:auto;
            align-self:flex-end;
            color:#022a37;
            border-bottom-right-radius:6px;
        }
        .msg.bot {
            background: linear-gradient(180deg,#f4f8ff,#eaf3ff);
            align-self:flex-start;
            color:#03305a;
            border-bottom-left-radius:6px;
            gap:12px;
            align-items:flex-start;
        }

        /* little "tail" on bubbles */
        .msg.bot::after, .msg.user::after {
            content: "";
            position: absolute;
            top: 16px;
            width: 14px;
            height: 14px;
            transform: rotate(45deg);
            box-shadow: 0 8px 14px rgba(4,29,66,0.03);
            border-radius: 2px;
            z-index: 0;
            background: inherit;
        }
        .msg.bot::after { left: -7px; }
        .msg.user::after { right: -7px; }

        /* responsive tweaks */

       @media (max-width: 720px) {
    .chat-panel { width: 92vw; right: 4%; bottom: 88px; }
    .chat-btn { width: 70px; height: 70px; }
    .chat-inner img, .chat-avatar { width: 38px; height: 38px; }
}

        @media (max-width: 720px) {
            .chat-panel { width: 92vw; right: 4%; bottom: 88px; }
            .chat-btn { width: 80px; height: 80px; }
        }

        /* Floating centered topbar with rounded corners */
        .topbar {
            position: fixed;
            top: 18px;
            left: 50%;
            transform: translateX(-50%);
            /* slightly wider floating bar (tiny bump) */
            width: calc(100% - 8px);
            max-width: 1800px;
            z-index: 80;
            border-radius: 30px;
            background: rgba(218, 218, 218, 0.6);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            box-shadow: 0 12px 40px rgba(2,6,23,0.12);
            border: 1px solid rgba(255,255,255,0.6);
        }

        @media (max-width: 768px) {
            .topbar { width: calc(100% - 24px); top: 12px; }
        }

        /* Reduce top padding since topbar is now floating */
        body { padding-top: 32px; }

    </style>
</head>
<body id="dashboard" class="antialiased">

    
   <!-- Top Navigation Bar -->
<header class="shadow animate-fade-in-down topbar">
    <div class="topbar-inner">
        <div class="topbar-brand">
            <span class="text-3xl font-bold logo-i logo-script">I</span>
            <span class="text-lg font-bold logo-serif">nkwise</span>
        </div>

        <nav id="mainNav" class="hidden md:flex topbar-nav" role="navigation">
            <a href="#dashboard" class="topbar-link">Home</a>
            <a href="#categories" class="topbar-link">Categories</a>
            <a href="#templates" class="topbar-link">Template</a>
            <a href="#about" class="topbar-link">About</a>
            <a href="#contact" class="topbar-link">Contact</a>
        </nav>

        <div class="hidden md:flex items-center gap-3 ml-auto">
            <form action="<?php echo e(url('/search')); ?>" method="GET" class="flex items-center gap-2">
                <div class="relative">
                    <input type="text" name="query" placeholder="Search..."
                           class="w-44 border border-gray-300 rounded-full px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-[#06b6d4] focus:border-transparent transition-all pr-8" />
                    <button type="submit" class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/>
                        </svg>
                    </button>
                </div>
            </form>

            <?php if(auth()->guard()->guest()): ?>
                <a href="<?php echo e(route('dashboard', ['modal' => 'login'])); ?>" id="openLogin"
                   class="px-4 py-1.5 text-xs font-semibold text-white rounded-full hover:shadow-md transition-all focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-[#a6b7ff]"
                   style="background: linear-gradient(90deg, #000000, #737373);">
                    Sign in
                </a>
            <?php endif; ?>

            <?php if(auth()->guard()->check()): ?>
                <div class="relative group">
                    <button id="userDropdownBtn" class="flex items-center gap-1.5 text-sm text-gray-700 hover:text-gray-900 font-medium">
                        <span><?php echo e(Auth::user()->customer?->first_name ?? Auth::user()->email ?? 'Customer'); ?></span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <div id="userDropdownMenu"
                         class="absolute right-0 mt-2 w-48 bg-white rounded shadow-lg opacity-0 group-hover:opacity-100 pointer-events-none group-hover:pointer-events-auto transition-opacity duration-200 z-50 hidden group-hover:block">
                        <a href="<?php echo e(route('customerprofile.index')); ?>"
                           class="block px-4 py-2 text-gray-700 hover:bg-[#e0f7fa] transition-colors">My Account</a>
                        <a href="<?php echo e(route('customer.my_purchase.completed')); ?>" class="block px-4 py-2 text-gray-700 hover:bg-[#e0f7fa] transition-colors">My Purchase</a>
                        <a href="<?php echo e(route('customer.favorites')); ?>" class="block px-4 py-2 text-gray-700 hover:bg-[#e0f7fa] transition-colors">My Favorites</a>
                        <form method="POST" action="<?php echo e(route('customer.logout')); ?>">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-[#e0f7fa] transition-colors">Logout</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="flex items-center gap-2 md:hidden">
            <button id="mobileSearchBtn" class="p-1.5 rounded-md focus-ring" aria-label="Toggle search" title="Search">
                <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/></svg>
            </button>
            <button id="mobileNavBtn" class="p-1.5 rounded-md focus-ring" aria-label="Toggle navigation" aria-controls="mainNav" aria-expanded="false">
                <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
        </div>
    </div>

    <div id="mobileSearch" class="hidden md:hidden w-full px-4 mt-2">
        <form action="<?php echo e(url('/search')); ?>" method="GET" class="relative">
            <input type="text" name="query" placeholder="Search..." class="w-full border rounded-lg px-3 py-2 pr-10 focus:outline-none focus:ring focus:ring-[#06b6d4]" />
            <button type="submit" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/>
                </svg>
            </button>
        </form>
    </div>
</header>
        
<script>
document.addEventListener('DOMContentLoaded', function () {
    // inject icons into header right-side (only on desktop)
    try {
        const headerRight = document.querySelector('header .hidden.md\\:flex.items-center.gap-3');
        // check if icons already present anywhere
        if (!document.querySelector('.nav-icon-button')) {
            const notifications = document.createElement('a');
            notifications.className = 'nav-icon-button';
            notifications.setAttribute('href', '<?php echo e(route('customer.notifications')); ?>');
            notifications.setAttribute('aria-label', 'Notifications');
            notifications.setAttribute('title', 'Notifications');
            notifications.innerHTML = '<i class="fa-regular fa-bell" style="color: black;" aria-hidden="true"></i>';

            // Add notification badge if there are unread notifications
            <?php if(auth()->guard()->check()): ?>
                <?php
                    $unreadCount = auth()->user()->unreadNotifications()->count();
                ?>
                <?php if($unreadCount > 0): ?>
                    const badge = document.createElement('span');
                    badge.className = 'notification-badge';
                    badge.textContent = '<?php echo e($unreadCount); ?>';
                    notifications.appendChild(badge);
                <?php endif; ?>
            <?php endif; ?>

            const fav = document.createElement('a');
            fav.className = 'nav-icon-button';
            fav.setAttribute('href', '<?php echo e(route('customer.favorites')); ?>');
            fav.setAttribute('aria-label', 'My favorites');
            fav.setAttribute('title', 'My favorites');
            fav.innerHTML = '<i class="fa-regular fa-heart" style="color: black;" aria-hidden="true"></i>';

            const cart = document.createElement('a');
            cart.className = 'nav-icon-button';
            cart.setAttribute('href', '/order/addtocart');
            cart.setAttribute('aria-label', 'My cart');
            cart.setAttribute('title', 'My cart');
            cart.innerHTML = '<i class="fa-solid fa-bag-shopping" style="color: black;" aria-hidden="true"></i>';

            if (headerRight) {
                // Find the search form
                const searchForm = headerRight.querySelector('form');
                if (searchForm) {
                    const iconsWrap = document.createElement('div');
                    iconsWrap.className = 'flex items-center gap-2';
                    iconsWrap.appendChild(notifications);
                    iconsWrap.appendChild(fav);
                    iconsWrap.appendChild(cart);
                    // insert after the search form
                    searchForm.insertAdjacentElement('afterend', iconsWrap);
                } else {
                    const container = document.createElement('div');
                    container.className = 'flex items-center gap-2';
                    container.appendChild(notifications);
                    container.appendChild(fav);
                    container.appendChild(cart);
                    headerRight.insertBefore(container, headerRight.firstChild);
                }
            }
        }
    } catch (e) { console.error('Icon injection error:', e); }

    // Attach behavior: check server order, create from sessionStorage if missing, then redirect to /order/addtocart
    const storageKey = 'inkwise-finalstep';
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    const icons = Array.from(document.querySelectorAll('.nav-icon-button'));
    if (!icons.length) return;

    const serverHasOrder = async () => {
        try {
            const res = await fetch('/order/summary.json', { method: 'GET', headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
            return res.ok;
        } catch (e) { return false; }
    };

    const createOrderFromSummary = async (summary) => {
        if (!summary) return false;
        const pid = summary.productId ?? summary.product_id ?? null;
        const quantity = Number(summary.quantity ?? 10);
        if (!pid) return false;
        try {
            const res = await fetch('/order/cart/items', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {})
                },
                credentials: 'same-origin',
                body: JSON.stringify({ product_id: Number(pid), quantity: Number(quantity) })
            });
            return res.ok;
        } catch (e) { return false; }
    };

    icons.forEach((icon) => {
        try {
            if (icon.getAttribute && icon.getAttribute('aria-disabled') === 'true') {
                icon.setAttribute('data-was-aria-disabled', 'true');
                icon.removeAttribute('aria-disabled');
                try { icon.style.pointerEvents = 'auto'; } catch (e) {}
                try { icon.setAttribute('tabindex', '0'); } catch (e) {}
                try { icon.setAttribute('role', 'button'); } catch (e) {}
                icon.addEventListener('keydown', (ev) => { if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); icon.click(); } });
            }
        } catch (e) { /* ignore */ }

        icon.addEventListener('click', async (e) => {
            // Skip order logic for notification bell icon
            if (icon.querySelector('i.fi-br-bell')) {
                return; // Let it go to the notifications page
            }

            try {
                e.preventDefault();
                if (await serverHasOrder()) { window.location.href = '/order/addtocart'; return; }
                let raw = null; try { raw = window.sessionStorage.getItem(storageKey); } catch (err) { raw = null; }
                let summary = null; try { summary = raw ? JSON.parse(raw) : null; } catch (err) { summary = null; }
                if (summary && (summary.productId || summary.product_id)) {
                    const created = await createOrderFromSummary(summary);
                    if (created) { window.location.href = '/order/addtocart'; return; }
                }
                const href = icon.getAttribute('href');
                if (href && href !== '#') { window.location.href = href; return; }
                window.location.href = '/order/addtocart';
            } catch (err) { window.location.href = '/order/summary'; }
        });
    });
});
</script>


<div class="py-2 px-4">
        <?php echo $__env->yieldContent('content'); ?>
    </div>

    <!-- Main Content -->
    <main class="hero-section" style="padding-top: 36px !important; background-position: center top !important; min-height: calc(100vh - 40px) !important;">
        <div class="hero-inner">
            <div class="hero-visual">
                <div class="hero-frame">
                    <!-- Masked video preview: wedding.mp4 (muted, autoplay, loop) with wed.jpg as poster -->
                    <video class="hero-frame-video" autoplay muted loop playsinline poster="<?php echo e(asset('customerVideo/Video/wed.jpg')); ?>" aria-hidden="true">
                        <source src="<?php echo e(asset('customerVideo/Video/wedding.mp4')); ?>" type="video/mp4">
                        <!-- Fallback image for browsers without video support -->
                        <img src="<?php echo e(asset('customerVideo/Video/wed.jpg')); ?>" alt="Wedding preview" class="hero-frame-img">
                    </video>
                </div>
            </div>

            <div class="hero-content layout-container">
                <h1 class="hero-title">
                    <span class="hero-title-invitation">INVITATION</span>
                    <span class="hero-title-maker">MAKER</span>
                </h1>

                <p class="hero-tagline">Custom Invitations &amp; Giveaways Crafted with Care.</p>

                <div class="hero-actions">
                    <?php if(auth()->guard()->check()): ?>
                        <a href="<?php echo e(route('templates.wedding.invitations')); ?>" class="hero-btn hero-btn--primary focus-ring">Order Now</a>
                    <?php else: ?>
                        <a href="<?php echo e(route('dashboard', ['modal' => 'login'])); ?>" class="hero-btn hero-btn--primary focus-ring">Order Now</a>
                    <?php endif; ?>
                    <a href="#categories" class="hero-btn hero-btn--ghost focus-ring">View Design</a>
                </div>
            </div>
        </div>
    </main>


<?php echo $__env->make('auth.customer.login', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>


<div class="layout-stack">
    
    <?php echo $__env->make('customer.partials.templates', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <?php echo $__env->make('customer.partials.categories', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <?php echo $__env->make('customer.partials.about', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <?php echo $__env->make('customer.partials.contact', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>

<!-- Chat bot AI assistance widget -->
<?php echo $__env->make('customer.partials.chatbot', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<!-- Optional: If you want to replace the placeholder bot replies with a real AI API call,
     replace the setTimeout block above with a fetch() to your server endpoint that proxies to OpenAI or another model. -->


<script>
    // Mobile nav & search toggle
    (function () {
        var btn = document.getElementById('mobileNavBtn');
        var nav = document.getElementById('mainNav');
        var searchBtn = document.getElementById('mobileSearchBtn');
        var searchPanel = document.getElementById('mobileSearch');

        function openNav() {
            nav.classList.remove('hidden');
            nav.classList.add('mobile-open');
            btn.setAttribute('aria-expanded', 'true');
        }

        function closeNav() {
            nav.classList.add('hidden');
            nav.classList.remove('mobile-open');
            btn.setAttribute('aria-expanded', 'false');
        }

        if (btn && nav) {
            btn.addEventListener('click', function () {
                if (nav.classList.contains('hidden')) {
                    openNav();
                } else {
                    closeNav();
                }
            });
        }

        if (searchBtn && searchPanel) {
            searchBtn.addEventListener('click', function () {
                if (searchPanel.classList.contains('hidden')) {
                    searchPanel.classList.remove('hidden');
                    searchPanel.classList.add('block');
                } else {
                    searchPanel.classList.remove('block');
                    searchPanel.classList.add('hidden');
                }
            });
        }

        // Close nav when resizing to desktop
        window.addEventListener('resize', function () {
            if (window.innerWidth >= 768) {
                nav.classList.remove('hidden');
                nav.classList.remove('mobile-open');
                btn.setAttribute('aria-expanded', 'false');
                if (searchPanel) {
                    searchPanel.classList.add('hidden');
                }
            } else {
                nav.classList.add('hidden');
            }
        });
    })();

    // Add keyboard-visible focus ring for better accessibility
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Tab') {
            document.body.classList.add('user-is-tabbing');
        }
    });
    document.addEventListener('mousedown', function () {
        document.body.classList.remove('user-is-tabbing');
    });

    // Optional: close mobile nav when clicking a link (improves UX)
    (function () {
        var nav = document.getElementById('mainNav');
        if (!nav) return;
        nav.addEventListener('click', function (e) {
            var target = e.target.closest('a');
            if (!target) return;
            if (window.innerWidth < 768) {
                nav.classList.remove('block');
                nav.classList.add('hidden');
            }
        });
    })();

    // Show login modal if password was just reset, login requested, or login failed
    <?php if(session('status') && str_contains(session('status'), 'Password reset successfully')): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var loginModal = document.getElementById('loginModal');
            if (loginModal) {
                loginModal.classList.remove('hidden');
                loginModal.classList.add('flex');
            }
        });
    </script>
    <?php elseif(request('show_login')): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var loginModal = document.getElementById('loginModal');
            if (loginModal) {
                loginModal.classList.remove('hidden');
                loginModal.classList.add('flex');
            }
        });
    </script>
    <?php elseif(session('show_login_modal') || $errors->has('email') || $errors->has('password')): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var loginModal = document.getElementById('loginModal');
            if (loginModal) {
                loginModal.classList.remove('hidden');
                loginModal.classList.add('flex');
            }
        });
    </script>
    <?php endif; ?>
</script>

</body>
</html>
<?php /**PATH C:\Users\leanne\xampp\htdocs\InkWise-Web\ink-wise\resources\views/customer/dashboard.blade.php ENDPATH**/ ?>