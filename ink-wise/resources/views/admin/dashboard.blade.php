@extends('layouts.admin')

@section('title', 'Dashboard')


@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-css/dashboard.css') }}">
        <style>
                .page-header {
                        display: flex;
                        justify-content: space-between;
                        align-items: flex-end;
                        gap: 1rem;
                        flex-wrap: wrap;
                }

                .page-action-button {
                        display: inline-flex;
                        align-items: center;
                        gap: 0.5rem;
                        padding: 0.75rem 1.5rem;
                        border-radius: 999px;
                        font-weight: 600;
                        text-decoration: none;
                        background: linear-gradient(120deg, #6a2ebc, #3cd5c8);
                        color: #fff;
                        box-shadow: 0 10px 20px -12px rgba(106, 46, 188, 0.75);
                        transition: transform 0.2s ease, box-shadow 0.2s ease;
                }

                .page-action-button:hover {
                        transform: translateY(-1px);
                        box-shadow: 0 14px 28px -14px rgba(106, 46, 188, 0.85);
                        color: #fff;
                }

                .page-action-button i {
                        font-size: 1rem;
                }

        /* Enhanced section spacing and visual hierarchy */
        .dashboard-page > section {
            margin-bottom: 2rem;
        }

        .dashboard-page > section:last-of-type {
            margin-bottom: 0;
        }

        /* Improved summary cards styling */
        .summary-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            margin: 1.5rem 0;
        }

        .summary-card {
            background: var(--admin-surface);
            border-radius: 16px;
            border: 1px solid rgba(148, 185, 255, 0.15);
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .summary-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #6a2ebc, #3cd5c8);
        }

        .summary-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        }

        .analytics-card {
            background: var(--admin-surface);
            border-radius: 18px;
            border: 1px solid rgba(148, 185, 255, 0.18);
            box-shadow: var(--admin-shadow-soft);
            padding: 24px;
            display: grid;
            gap: 16px;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .analytics-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .analytics-card__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .analytics-card__header h2 {
            margin: 0;
            font-size: 1.18rem;
        }

        .analytics-card__tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.78rem;
            padding: 0.35rem 0.85rem;
            border-radius: 999px;
            background: rgba(90, 141, 224, 0.12);
            color: var(--admin-accent-strong);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .analytics-card p {
            margin: 0;
            color: var(--admin-text-secondary);
            line-height: 1.65;
        }

        .analytics-card__list {
            margin: 0;
            padding-left: 1.2rem;
            display: grid;
            gap: 6px;
            color: var(--admin-text-secondary);
        }

        .insights-grid {
            display: grid;
            gap: 18px;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        }

        .insight-stat {
            display: grid;
            gap: 8px;
            padding: 16px;
            border-radius: 16px;
            background: rgba(148, 185, 255, 0.08);
            border: 1px solid rgba(148, 185, 255, 0.12);
        }

        .insight-stat--primary {
            background: linear-gradient(135deg, rgba(58, 133, 244, 0.18), rgba(105, 231, 206, 0.18));
            border-color: rgba(58, 133, 244, 0.22);
        }

        .insight-label {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--admin-text-secondary);
            font-weight: 700;
        }

        .insight-value {
            font-size: 1.65rem;
            font-weight: 700;
            color: var(--admin-text-primary);
        }

        .insight-delta {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.86rem;
            font-weight: 600;
            padding: 0.35rem 0.65rem;
            border-radius: 999px;
            background: rgba(148, 185, 255, 0.16);
            color: var(--admin-text-secondary);
            width: fit-content;
        }

        .insight-delta--up {
            background: rgba(56, 193, 114, 0.12);
            color: #25a86b;
        }

        .insight-delta--down {
            background: rgba(255, 107, 107, 0.12);
            color: #ff6b6b;
        }

        .insight-delta--flat {
            background: rgba(161, 174, 192, 0.16);
            color: var(--admin-text-secondary);
        }

        .insight-delta__icon {
            font-size: 0.9rem;
            line-height: 1;
        }

        .insight-footnote {
            margin: 0;
            font-size: 0.76rem;
            color: var(--admin-text-secondary);
        }

        .insight-meta-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .insight-meta-card {
            padding: 18px;
            border-radius: 16px;
            background: rgba(148, 185, 255, 0.08);
            border: 1px dashed rgba(148, 185, 255, 0.24);
            display: grid;
            gap: 6px;
        }

        .insight-meta-label {
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--admin-text-secondary);
            font-weight: 700;
        }

        .insight-meta-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--admin-text-primary);
        }

        .insight-meta-caption {
            font-size: 0.82rem;
            color: var(--admin-text-secondary);
        }

        .analytics-card--design {
            grid-template-rows: auto 1fr;
        }

        .design-highlight {
            display: grid;
            gap: 16px;
            grid-template-columns: minmax(120px, 160px) 1fr;
            align-items: stretch;
        }

        .design-highlight__image {
            border-radius: 14px;
            background: rgba(148, 185, 255, 0.16);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .design-highlight__image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .design-highlight__placeholder {
            text-align: center;
            font-size: 0.82rem;
            color: var(--admin-text-secondary);
            padding: 18px;
        }

        .design-highlight__meta {
            display: grid;
            gap: 6px;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            margin: 0;
        }

        .design-highlight__meta dt {
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--admin-text-secondary);
            font-weight: 700;
        }

        .design-highlight__meta dd {
            margin: 0 0 12px;
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--admin-text-primary);
        }

        .design-highlight__cta {
            margin-top: auto;
            width: fit-content;
        }

        .analytics-card__empty {
            margin: 0;
            color: var(--admin-text-secondary);
            font-size: 0.88rem;
        }

        @media (max-width: 640px) {
            .design-highlight {
                grid-template-columns: 1fr;
            }
        }

        /* Review styles for admin dashboard */
        .reviews-list {
            display: grid;
            gap: 12px;
        }

        .review-item {
            background: rgba(148, 185, 255, 0.06);
            border: 1px solid rgba(148, 185, 255, 0.12);
            border-radius: 12px;
            padding: 18px;
            position: relative;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .review-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .review-item.needs-reply {
            border-left: 4px solid #f59e0b;
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.04), rgba(245, 158, 11, 0.08));
        }

        .review-item.replied {
            border-left: 4px solid #10b981;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.04), rgba(16, 185, 129, 0.08));
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            flex-wrap: wrap;
            gap: 8px;
        }

        .review-rating {
            display: flex;
            gap: 2px;
        }

        .review-rating i {
            color: #fbbf24;
            font-size: 14px;
        }

        .review-date {
            font-size: 12px;
            color: var(--admin-text-secondary);
            font-weight: 500;
        }

        .review-status {
            font-size: 10px;
            font-weight: 700;
            padding: 4px 8px;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .review-status.replied {
            background: rgba(16, 185, 129, 0.12);
            color: #065f46;
        }

        .review-status.needs-reply {
            background: rgba(245, 158, 11, 0.12);
            color: #92400e;
        }

        .review-comment {
            font-size: 14px;
            line-height: 1.4;
            color: var(--admin-text-primary);
            margin-bottom: 8px;
        }

        .review-author {
            font-size: 12px;
            color: var(--admin-text-secondary);
            font-style: italic;
        }

        .review-card-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        }

        .review-card {
            border-radius: 16px;
            border: 1px solid rgba(148, 185, 255, 0.18);
            background: rgba(148, 185, 255, 0.08);
            padding: 1.1rem;
            display: grid;
            gap: 0.75rem;
            position: relative;
        }

        .review-card::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 16px;
            background: linear-gradient(135deg, rgba(106, 46, 188, 0.08), rgba(60, 213, 200, 0.08));
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .review-card:hover::before {
            opacity: 1;
        }

        .review-card__content {
            position: relative;
            display: grid;
            gap: 0.6rem;
        }

        .review-card__header {
            display: flex;
            justify-content: space-between;
            gap: 0.5rem;
            align-items: flex-start;
        }

        .review-card__rating {
            display: inline-flex;
            gap: 2px;
        }

        .review-card__meta {
            font-size: 0.78rem;
            color: var(--admin-text-secondary);
            display: grid;
            gap: 0.35rem;
        }

        .review-card__comment {
            font-size: 0.9rem;
            line-height: 1.5;
            color: var(--admin-text-primary);
        }

        .review-card__status {
            font-size: 0.7rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-weight: 700;
            border-radius: 999px;
            padding: 0.25rem 0.6rem;
            background: rgba(148, 185, 255, 0.24);
            color: var(--admin-text-secondary);
        }

        .review-card--needs-reply .review-card__status {
            background: rgba(245, 158, 11, 0.18);
            color: #92400e;
        }

        .review-card--replied .review-card__status {
            background: rgba(16, 185, 129, 0.18);
            color: #065f46;
        }

        /* Enhanced stock section styling */
        .dashboard-stock {
            background: var(--admin-surface);
            border-radius: 18px;
            border: 1px solid rgba(148, 185, 255, 0.18);
            box-shadow: var(--admin-shadow-soft);
            padding: 24px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .dashboard-stock:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .section-title {
            margin: 0 0 0.25rem 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--admin-text-primary);
        }

        .section-subtitle {
            margin: 0;
            font-size: 0.88rem;
            color: var(--admin-text-secondary);
        }

        .table-wrapper {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(148, 185, 255, 0.15);
            background: rgba(148, 185, 255, 0.02);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background: rgba(148, 185, 255, 0.08);
            padding: 12px 16px;
            text-align: left;
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--admin-text-secondary);
            border-bottom: 1px solid rgba(148, 185, 255, 0.15);
        }

        .table td {
            padding: 12px 16px;
            border-bottom: 1px solid rgba(148, 185, 255, 0.08);
            color: var(--admin-text-primary);
        }

        .table tbody tr:hover {
            background: rgba(148, 185, 255, 0.04);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stock-ok {
            background: rgba(16, 185, 129, 0.12);
            color: #065f46;
        }

        .stock-low {
            background: rgba(245, 158, 11, 0.12);
            color: #92400e;
        }

        .stock-critical {
            background: rgba(239, 68, 68, 0.12);
            color: #991b1b;
        }

        .status-label {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-label.ok {
            background: rgba(16, 185, 129, 0.12);
            color: #065f46;
        }

        .status-label.low {
            background: rgba(245, 158, 11, 0.12);
            color: #92400e;
        }

        .status-label.out {
            background: rgba(239, 68, 68, 0.12);
            color: #991b1b;
        }

        .overview-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            margin: 2rem 0;
        }

        .overview-card {
            background: var(--admin-surface);
            border: 1px solid rgba(148, 185, 255, 0.12);
            border-radius: 16px;
            padding: 1.25rem;
            display: grid;
            gap: 0.5rem;
            position: relative;
            overflow: hidden;
            box-shadow: var(--admin-shadow-soft);
        }

        .overview-card::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(106, 46, 188, 0.1), rgba(60, 213, 200, 0.1));
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .overview-card:hover::after {
            opacity: 1;
        }

        .overview-card__label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--admin-text-secondary);
            font-weight: 700;
        }

        .overview-card__value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--admin-text-primary);
        }

        .overview-card__meta {
            font-size: 0.78rem;
            color: var(--admin-text-secondary);
        }

        .dashboard-grid {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        }

        .dashboard-grid--compact {
            justify-content: start;
        }

        .dashboard-card {
            background: var(--admin-surface);
            border: 1px solid rgba(148, 185, 255, 0.18);
            border-radius: 18px;
            padding: 1.5rem;
            box-shadow: var(--admin-shadow-soft);
            display: grid;
            gap: 1.25rem;
        }

        .dashboard-card--wide {
            grid-column: 1 / -1;
        }

        .dashboard-card--narrow {
            max-width: 620px;
            width: 100%;
            justify-self: start;
        }

        .dashboard-card__header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
        }

        .dashboard-card__title {
            margin: 0;
            font-size: 1.18rem;
            font-weight: 700;
        }

        .status-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            background: rgba(148, 185, 255, 0.12);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--admin-text-secondary);
        }

        .status-chip--attention {
            background: rgba(245, 158, 11, 0.12);
            color: #92400e;
        }

        .status-chip--success {
            background: rgba(16, 185, 129, 0.12);
            color: #065f46;
        }

        .status-chip--danger {
            background: rgba(239, 68, 68, 0.12);
            color: #991b1b;
        }

        .order-list {
            display: grid;
            gap: 1rem;
        }

        .order-item {
            border: 1px solid rgba(148, 185, 255, 0.12);
            border-radius: 14px;
            padding: 1rem;
            display: grid;
            gap: 0.75rem;
            background: rgba(148, 185, 255, 0.06);
        }

        .order-item.highlight-custom {
            border-left: 4px solid #6a2ebc;
            background: linear-gradient(135deg, rgba(106, 46, 188, 0.08), rgba(60, 213, 200, 0.05));
        }

        .order-item__header {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .order-item__title {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
        }

        .order-item__meta {
            font-size: 0.78rem;
            color: var(--admin-text-secondary);
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .order-item__actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .order-update-form {
            display: inline-flex;
            gap: 0.5rem;
            align-items: center;
        }

        .order-update-form select {
            border-radius: 8px;
            border: 1px solid rgba(148, 185, 255, 0.4);
            padding: 0.45rem 0.75rem;
            font-size: 0.85rem;
        }

        .order-update-form button {
            background: linear-gradient(120deg, #6a2ebc, #3cd5c8);
            color: #fff;
            border: none;
            border-radius: 999px;
            padding: 0.45rem 0.95rem;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .order-update-form button:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px -10px rgba(106, 46, 188, 0.7);
        }

        .timeline-list {
            display: grid;
            gap: 0.5rem;
            max-height: 280px;
            overflow-y: auto;
            padding-right: 0.5rem;
        }

        .timeline-entry {
            border-left: 3px solid rgba(148, 185, 255, 0.35);
            padding-left: 0.75rem;
        }

        .timeline-entry__title {
            font-size: 0.85rem;
            font-weight: 600;
            margin: 0 0 0.25rem;
        }

        .timeline-entry__meta {
            font-size: 0.75rem;
            color: var(--admin-text-secondary);
        }

        .sales-metrics {
            display: grid;
            gap: 0.75rem;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        }

        .sales-metric {
            background: rgba(148, 185, 255, 0.08);
            border-radius: 12px;
            padding: 0.9rem;
            border: 1px solid rgba(148, 185, 255, 0.12);
        }

        .sales-metric strong {
            display: block;
            font-size: 1.2rem;
            margin-top: 0.25rem;
        }

        .sales-chart-wrapper {
            position: relative;
            min-height: 240px;
        }

        .sales-performance-grid {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: minmax(260px, 1.6fr) minmax(220px, 1fr);
            align-items: start;
        }

        .sales-performance-sidebar {
            display: grid;
            gap: 1rem;
        }

        @media (max-width: 960px) {
            .sales-performance-grid {
                grid-template-columns: 1fr;
            }
        }

        .insight-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .insight-card {
            border: 1px dashed rgba(148, 185, 255, 0.24);
            border-radius: 16px;
            padding: 1.1rem;
            background: rgba(148, 185, 255, 0.08);
            display: grid;
            gap: 0.5rem;
        }

        .shortcut-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .shortcut-card {
            border: 1px solid rgba(148, 185, 255, 0.15);
            border-radius: 16px;
            padding: 1.1rem;
            background: rgba(148, 185, 255, 0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            display: grid;
            gap: 0.5rem;
        }

        .shortcut-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.08);
        }

        .activity-feed {
            display: grid;
            gap: 0.75rem;
            max-height: 320px;
            overflow-y: auto;
            padding-right: 0.5rem;
        }

        .activity-entry {
            border-left: 3px solid rgba(148, 185, 255, 0.3);
            padding-left: 0.75rem;
            display: grid;
            gap: 0.25rem;
        }

        .activity-entry__timestamp {
            font-size: 0.75rem;
            color: var(--admin-text-secondary);
        }

        .inventory-highlights {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            margin-bottom: 1.5rem;
        }

        .inventory-highlight-card {
            border: 1px solid rgba(148, 185, 255, 0.18);
            border-radius: 14px;
            padding: 1rem;
            background: rgba(148, 185, 255, 0.06);
            display: grid;
            gap: 0.6rem;
        }

        .movement-log {
            display: grid;
            gap: 0.6rem;
            max-height: 220px;
            overflow-y: auto;
            padding-right: 0.5rem;
        }

        .movement-log__entry {
            border-left: 3px solid rgba(148, 185, 255, 0.35);
            padding-left: 0.65rem;
            font-size: 0.85rem;
        }

        .movement-log__meta {
            font-size: 0.75rem;
            color: var(--admin-text-secondary);
        }

        .alert-banner {
            border-radius: 16px;
            padding: 1rem 1.25rem;
            display: grid;
            gap: 0.4rem;
            margin-bottom: 1.25rem;
        }

        .alert-banner--critical {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.32);
            color: #991b1b;
        }

        .alert-banner--warning {
            background: rgba(245, 158, 11, 0.12);
            border: 1px solid rgba(245, 158, 11, 0.32);
            color: #92400e;
        }

        .announcement-board {
            background: rgba(148, 185, 255, 0.08);
            border: 1px dashed rgba(148, 185, 255, 0.24);
            border-radius: 16px;
            padding: 1.25rem;
            display: grid;
            gap: 0.75rem;
        }

        .announcement-board__item {
            background: rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            padding: 0.85rem;
            border: 1px solid rgba(148, 185, 255, 0.18);
        }

        @media (max-width: 768px) {
            .dashboard-card__header {
                flex-direction: column;
                align-items: stretch;
            }

            .order-item__header {
                flex-direction: column;
                align-items: flex-start;
            }

            .order-item__meta {
                gap: 0.5rem;
            }
        }
        </style>
@endpush

@section('content')
@php
    $metrics = $dashboardMetrics ?? [
    'ordersThisWeek' => 0,
    'revenueThisWeek' => 0,
    'averageOrderValue' => 0,
    'pendingOrders' => 0,
    'newOrders' => 0,
    'lowStock' => 0,
    'outOfStock' => 0,
    'totalStockUnits' => 0,
    'totalSkus' => 0,
    'ordersWoW' => ['change' => 0, 'percent' => 0, 'direction' => 'flat'],
    'revenueWoW' => ['change' => 0, 'percent' => 0, 'direction' => 'flat'],
    'inventoryRiskPercent' => 0,
    'stockCoverageDays' => null,
    ];
    $popular = $popularDesign ?? null;
    $overview = $overviewStats ?? [];
    $orderManagementData = $orderManagement ?? ['orders' => collect(), 'statusCounts' => [], 'paymentStatusCounts' => [], 'statusOptions' => []];
    $salesPreviewData = $salesPreview ?? ['daily' => 0, 'weekly' => 0, 'monthly' => 0, 'trend' => ['labels' => [], 'values' => []], 'bestSelling' => collect(), 'recentTransactions' => collect()];
    $inventoryMonitorData = $inventoryMonitor ?? ['lowStockMaterials' => collect(), 'outOfStockMaterials' => collect(), 'movementLogs' => collect()];
    $customerInsightsData = $customerInsights ?? ['topCustomers' => collect(), 'repeatCustomers' => 0, 'popularDesigns' => [], 'peakOrderDays' => collect()];
    $accountControlData = $accountControl ?? ['roleBreakdown' => [], 'staffStatusBreakdown' => [], 'recentStaff' => collect()];
    $systemShortcutsData = $systemShortcuts ?? [];
    $recentActivityData = collect($recentActivityFeed ?? []);
    $upcomingCalendarData = $upcomingCalendar ?? ['upcomingOrders' => collect(), 'calendarRoute' => route('admin.reports.pickup-calendar')];
    $materialAlertsData = $materialAlerts ?? [];
    $announcements = $dashboardAnnouncements ?? [];
    $reviewSnapshot = $customerReviewSnapshot ?? ['average' => null, 'count' => 0];
@endphp
<main class="admin-page-shell dashboard-page" role="main">
    {{-- ✅ Greeting Message --}}
    @if(session('success'))
        <div id="greetingMessage" class="dashboard-alert" role="alert" aria-live="polite">
            {{ session('success') }}
        </div>
    @endif

    <header class="page-header">
        <div>
            <h1 class="page-title">Dashboard Overview</h1>
            <p class="page-subtitle">Quick look at orders and stock health.</p>
        </div>

    </header>

    @foreach($materialAlertsData as $alert)
        @php
            $alertItems = collect($alert['items'] ?? [])->take(5)->implode(', ');
        @endphp
        <div class="alert-banner alert-banner--{{ $alert['type'] === 'critical' ? 'critical' : 'warning' }}" role="alert">
            <strong>{{ $alert['type'] === 'critical' ? 'Critical inventory alert' : 'Upcoming restock needed' }}</strong>
            <span>{{ $alert['message'] }}</span>
            @if($alertItems)
                <span><em>Focus:</em> {{ $alertItems }}</span>
            @endif
        </div>
    @endforeach

    @if(!empty($announcements))
        <section class="announcement-board" aria-label="System announcements">
            <h2 class="section-title" style="margin:0;">System Announcements</h2>
            @foreach($announcements as $announcement)
                <div class="announcement-board__item">
                    <strong>{{ $announcement['title'] ?? 'Notice' }}</strong>
                    <p style="margin:0.35rem 0;">{{ $announcement['message'] ?? '' }}</p>
                    @if(!empty($announcement['timestamp']))
                        <small class="review-author">{{ \Carbon\Carbon::parse($announcement['timestamp'])->format('M d, Y h:i A') }}</small>
                    @endif
                </div>
            @endforeach
        </section>
    @endif

    <section class="overview-grid" aria-label="Quick overview">
        <a href="{{ route('admin.orders.index') }}" class="overview-card" style="text-decoration:none; color:inherit;">
            <span class="overview-card__label">Total Orders</span>
            <span class="overview-card__value">{{ number_format($overview['totalOrders'] ?? 0) }}</span>
            <span class="overview-card__meta">Lifetime orders processed</span>
        </a>
        <a href="{{ route('admin.reports.sales') }}" class="overview-card" style="text-decoration:none; color:inherit;">
            <span class="overview-card__label">Total Sales</span>
            <span class="overview-card__value">₱{{ number_format($overview['totalSales'] ?? 0, 2) }}</span>
            <span class="overview-card__meta">Aggregate revenue</span>
        </a>
        <a href="{{ route('admin.orders.index') }}" class="overview-card" style="text-decoration:none; color:inherit;">
            <span class="overview-card__label">Pending Orders</span>
            <span class="overview-card__value">{{ number_format($overview['pendingOrders'] ?? 0) }}</span>
            <span class="overview-card__meta">Awaiting action</span>
        </a>
        <a href="{{ route('admin.materials.index') }}" class="overview-card" style="text-decoration:none; color:inherit;">
            <span class="overview-card__label">Low Stock Items</span>
            <span class="overview-card__value">{{ number_format($overview['lowStock'] ?? 0) }}</span>
            <span class="overview-card__meta">Within reorder threshold</span>
        </a>
        <a href="{{ route('admin.materials.index') }}" class="overview-card" style="text-decoration:none; color:inherit;">
            <span class="overview-card__label">Out of Stock</span>
            <span class="overview-card__value">{{ number_format($overview['outOfStock'] ?? 0) }}</span>
            <span class="overview-card__meta">Immediate restock required</span>
        </a>
        <a href="{{ route('admin.customers.index') }}" class="overview-card" style="text-decoration:none; color:inherit;">
            <span class="overview-card__label">Customers</span>
            <span class="overview-card__value">{{ number_format($overview['totalCustomers'] ?? 0) }}</span>
            <span class="overview-card__meta">Registered customers</span>
        </a>
        <a href="{{ route('admin.users.index') }}" class="overview-card" style="text-decoration:none; color:inherit;">
            <span class="overview-card__label">Active Staff</span>
            <span class="overview-card__value">{{ number_format($overview['activeStaff'] ?? 0) }}</span>
            <span class="overview-card__meta">Staff accounts</span>
        </a>

    </section>

    @php
        $recentReviews = \App\Models\OrderRating::with(['customer', 'order'])
            ->latest('submitted_at')
            ->take(6)
            ->get();
        $outstandingReviews = \App\Models\OrderRating::whereNull('staff_reply')->count();
        $roundedAverageRating = $reviewSnapshot['average'] ? round($reviewSnapshot['average'], 1) : null;
    @endphp

    <section class="analytics-grid" aria-label="Sales and inventory analytics">
        <article class="analytics-card">
            <header class="analytics-card__header">
                <h2>Sales &amp; Inventory Insights</h2>
                <span class="analytics-card__tag">This Week</span>
            </header>
            @php
                $ordersDelta = $metrics['ordersWoW'] ?? ['change' => 0, 'percent' => 0, 'direction' => 'flat'];
                $revenueDelta = $metrics['revenueWoW'] ?? ['change' => 0, 'percent' => 0, 'direction' => 'flat'];
                $directionIcons = ['up' => '▲', 'down' => '▼', 'flat' => '⭘'];
                $deltaClasses = [
                    'up' => 'insight-delta insight-delta--up',
                    'down' => 'insight-delta insight-delta--down',
                    'flat' => 'insight-delta insight-delta--flat',
                ];
                $formatChange = function ($value, $decimals = 0) {
                    $numeric = (float) $value;
                    $formatted = number_format(abs($numeric), $decimals);
                    if ($numeric > 0) {
                        return '+' . $formatted;
                    }
                    if ($numeric < 0) {
                        return '-' . $formatted;
                    }
                    return '0';
                };
            @endphp

            <div class="insights-grid" role="list">
                <div class="insight-stat insight-stat--primary" role="listitem">
                    <span class="insight-label">Orders</span>
                    <div class="insight-value">{{ number_format($metrics['ordersThisWeek']) }}</div>
                    <span class="{{ $deltaClasses[$ordersDelta['direction']] ?? 'insight-delta' }}" aria-label="{{ $formatChange($ordersDelta['percent'], 1) }} percent versus last week">
                        <span class="insight-delta__icon" aria-hidden="true">{{ $directionIcons[$ordersDelta['direction']] ?? '⭘' }}</span>
                        <span>{{ $formatChange($ordersDelta['change']) }} ({{ $formatChange($ordersDelta['percent'], 1) }}%)</span>
                    </span>
                    <p class="insight-footnote">vs last week</p>
                </div>
                <div class="insight-stat insight-stat--primary" role="listitem">
                    <span class="insight-label">Revenue</span>
                    <div class="insight-value">₱{{ number_format($metrics['revenueThisWeek'], 2) }}</div>
                    <span class="{{ $deltaClasses[$revenueDelta['direction']] ?? 'insight-delta' }}" aria-label="{{ $formatChange($revenueDelta['percent'], 1) }} percent versus last week">
                        <span class="insight-delta__icon" aria-hidden="true">{{ $directionIcons[$revenueDelta['direction']] ?? '⭘' }}</span>
                        <span>{{ $formatChange($revenueDelta['change'], 2) }} ({{ $formatChange($revenueDelta['percent'], 1) }}%)</span>
                    </span>
                    <p class="insight-footnote">vs last week</p>
                </div>
                <div class="insight-stat" role="listitem">
                    <span class="insight-label">Avg. Order Value</span>
                    <div class="insight-value">₱{{ number_format($metrics['averageOrderValue'], 2) }}</div>
                    <p class="insight-footnote">Average basket size for the current week.</p>
                </div>
                <div class="insight-stat" role="listitem">
                    <span class="insight-label">Pending Orders</span>
                    <div class="insight-value">{{ number_format($metrics['pendingOrders']) }}</div>
                    <p class="insight-footnote">Queued for fulfillment or follow-up.</p>
                </div>
            </div>

            <div class="insight-meta-grid" role="list">
                <div class="insight-meta-card" role="listitem">
                    <span class="insight-meta-label">Inventory Risk Exposure</span>
                    <span class="insight-meta-value">{{ number_format((float) $metrics['inventoryRiskPercent'], 1) }}%</span>
                    <span class="insight-meta-caption">{{ number_format($metrics['lowStock']) }} low stock / {{ number_format($metrics['outOfStock']) }} out of stock</span>
                </div>
                <div class="insight-meta-card" role="listitem">
                    <span class="insight-meta-label">Stock Coverage</span>
                    <span class="insight-meta-value">
                        @if(!is_null($metrics['stockCoverageDays']))
                            {{ number_format($metrics['stockCoverageDays'], 1) }} days
                        @else
                            —
                        @endif
                    </span>
                    <span class="insight-meta-caption">{{ number_format($metrics['totalStockUnits']) }} units on hand across {{ number_format($metrics['totalSkus']) }} SKUs</span>
                </div>
            </div>
        </article>

        <article class="analytics-card analytics-card--design" aria-label="Popular design highlight">
            <header class="analytics-card__header">
                <h2>Popular Design</h2>
                @if($popular)
                    <span class="analytics-card__tag">{{ number_format($popular['orders']) }} orders</span>
                @endif
            </header>

            @if($popular)
                <div class="design-highlight">
                    <div class="design-highlight__image">
                        @if($popular['image'])
                            <img src="{{ $popular['image'] }}" alt="{{ $popular['name'] }} preview">
                        @else
                            <div class="design-highlight__placeholder">No preview available</div>
                        @endif
                    </div>
                    <div>
                        <dl class="design-highlight__meta">
                            <div>
                                <dt>Design</dt>
                                <dd>{{ $popular['name'] }}</dd>
                            </div>
                            <div>
                                <dt>Units Sold</dt>
                                <dd>{{ number_format($popular['quantity']) }}</dd>
                            </div>
                            <div>
                                <dt>Orders</dt>
                                <dd>{{ number_format($popular['orders']) }}</dd>
                            </div>
                        </dl>
                        @if(!empty($popular['product']))
                            <a href="{{ route('admin.products.edit', ['id' => $popular['product']->id]) }}" class="pill-link design-highlight__cta">
                                Manage design
                            </a>
                        @endif
                    </div>
                </div>
            @else
                <p class="analytics-card__empty">No design trends yet. Once orders flow in, the top-performing layout will surface here.</p>
            @endif
        </article>
        <article class="analytics-card" aria-label="Customer reviews snapshot">
            <header class="analytics-card__header">
                <h2>Customer Reviews</h2>
                <a href="{{ route('admin.reviews.index') }}" class="pill-link">Manage reviews</a>
            </header>
            <div class="insight-grid" style="margin-bottom:1rem;">
                <div class="insight-card">
                    <span class="insight-meta-label">Average rating</span>
                    <div style="display:flex; align-items:center; gap:0.75rem;">
                        <span class="insight-meta-value">{{ $roundedAverageRating ? number_format($roundedAverageRating, 1) : '—' }}</span>
                        <span class="review-card__rating" aria-hidden="true">
                            @for ($i = 1; $i <= 5; $i++)
                                <i class="fi fi-rr-star{{ ($reviewSnapshot['average'] ?? 0) >= $i - 0.3 ? '' : '-empty' }}" style="color:#fbbf24;"></i>
                            @endfor
                        </span>
                    </div>
                    <span class="insight-meta-caption">{{ number_format($reviewSnapshot['count'] ?? 0) }} total reviews recorded.</span>
                </div>
                <div class="insight-card">
                    <span class="insight-meta-label">Awaiting reply</span>
                    <span class="insight-meta-value">{{ number_format($outstandingReviews) }}</span>
                    <span class="insight-meta-caption">Reviews without staff responses yet.</span>
                </div>
            </div>
            @if($recentReviews->isNotEmpty())
                <div class="review-card-grid">
                    @foreach($recentReviews as $review)
                        @php
                            $cardStateClass = $review->staff_reply ? 'review-card--replied' : 'review-card--needs-reply';
                            $orderLabel = $review->order?->order_number ?? ('Order #' . ($review->order_id ?? ''));
                        @endphp
                        <article class="review-card {{ $cardStateClass }}">
                            <div class="review-card__content">
                                <div class="review-card__header">
                                    <span class="review-card__rating" aria-label="{{ $review->rating }} out of 5 stars">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <i class="fi fi-rr-star{{ $review->rating >= $i ? '' : '-empty' }}" style="color:#fbbf24;"></i>
                                        @endfor
                                    </span>
                                    <span class="review-card__status">{{ $review->staff_reply ? 'Replied' : 'Needs reply' }}</span>
                                </div>
                                <p class="review-card__comment">{{ trim((string) ($review->review ?? '')) !== '' ? $review->review : 'No comment provided.' }}</p>
                                <div class="review-card__meta">
                                    <span><strong>{{ $review->customer->full_name ?? 'Guest customer' }}</strong> · {{ $orderLabel }}</span>
                                    <span>{{ optional($review->submitted_at)->format('M d, Y h:i A') ?? 'Date unavailable' }}</span>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <p class="analytics-card__empty">No customer feedback yet. Reviews will appear here once received.</p>
            @endif
        </article>
    </section>

    <section class="dashboard-grid" aria-label="Sales performance">
        <article class="dashboard-card dashboard-card--wide">
            <header class="dashboard-card__header">
                <div>
                    <h2 class="dashboard-card__title">Sales Performance</h2>
                    <p class="section-subtitle" style="margin:0;">Track revenue pace and monitor leading products at a glance.</p>
                </div>
                <div class="order-item__actions" style="justify-content:flex-end;">
                    <a href="{{ route('admin.reports.sales') }}" class="pill-link">View full report</a>
                    <a href="{{ route('admin.reports.sales.export', ['type' => 'xlsx']) }}" class="pill-link">Download XLSX</a>
                </div>
            </header>
            @php
                $bestSellingProducts = collect($salesPreviewData['bestSelling'] ?? [])->take(5);
                $recentTransactions = collect($salesPreviewData['recentTransactions'] ?? [])->take(5);
            @endphp
            <div class="sales-metrics">
                <div class="sales-metric" aria-label="Daily sales">
                    <span>Daily</span>
                    <strong>₱{{ number_format($salesPreviewData['daily'] ?? 0, 2) }}</strong>
                </div>
                <div class="sales-metric" aria-label="Weekly sales">
                    <span>Weekly</span>
                    <strong>₱{{ number_format($salesPreviewData['weekly'] ?? 0, 2) }}</strong>
                </div>
                <div class="sales-metric" aria-label="Monthly sales">
                    <span>Monthly</span>
                    <strong>₱{{ number_format($salesPreviewData['monthly'] ?? 0, 2) }}</strong>
                </div>
            </div>
            <div class="sales-performance-grid">
                <div class="sales-chart-wrapper" role="img" aria-label="Sales trend chart">
                    <canvas id="salesTrendChart"
                        data-labels='@json($salesPreviewData['trend']['labels'] ?? [])'
                        data-values='@json($salesPreviewData['trend']['values'] ?? [])'>
                    </canvas>
                </div>
                <div class="sales-performance-sidebar">
                    <div class="insight-card" aria-label="Top products">
                        <span class="insight-meta-label">Top products this week</span>
                        <ul class="analytics-card__list" style="margin:0;">
                            @forelse($bestSellingProducts as $product)
                                <li><strong>{{ $product->label }}</strong> – {{ number_format($product->orders_count ?? 0) }} orders · {{ number_format($product->quantity ?? 0) }} units</li>
                            @empty
                                <li>No product performance data yet.</li>
                            @endforelse
                        </ul>
                    </div>
                    <div class="insight-card" aria-label="Recent transactions">
                        <span class="insight-meta-label">Recent transactions</span>
                        <ul class="analytics-card__list" style="margin:0;">
                            @forelse($recentTransactions as $transaction)
                                @php
                                    $transactionLabel = $transaction->reference ?? $transaction->order?->order_number ?? ('Payment #' . ($transaction->id ?? ''));
                                    $transactionAmount = $transaction->amount ?? $transaction->total ?? 0;
                                    $transactionStatus = Str::title(str_replace('_', ' ', $transaction->status ?? 'completed'));
                                    $transactionTime = optional($transaction->created_at)->diffForHumans() ?? 'Just now';
                                    $transactionMethod = Str::title(str_replace('_', ' ', $transaction->method ?? $transaction->payment_method ?? 'payment'));
                                @endphp
                                <li>
                                    <strong>{{ $transactionLabel }}</strong> – ₱{{ number_format($transactionAmount, 2) }}
                                    <span class="insight-meta-caption" style="display:block;">
                                        {{ $transactionMethod }} · {{ $transactionStatus }} · {{ $transactionTime }}
                                    </span>
                                </li>
                            @empty
                                <li>No payment records yet.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </article>
    </section>

    
    <section class="dashboard-grid" aria-label="Customer insights">
        <article class="dashboard-card">
            <header class="dashboard-card__header">
                <h2 class="dashboard-card__title">Customer Insights</h2>
                <a href="{{ route('admin.customers.index') }}" class="pill-link">View customers</a>
            </header>
            <div class="insight-grid">
                <div class="insight-card">
                    <span class="insight-meta-label">Repeat customers</span>
                    <span class="insight-meta-value">{{ number_format($customerInsightsData['repeatCustomers'] ?? 0) }}</span>
                    <span class="insight-meta-caption">Customers with 2+ completed orders.</span>
                </div>
            </div>
            <div style="margin-top:1rem;">
                <h3 style="margin:0 0 0.5rem 0; font-size:1rem;">Top customers by spend</h3>
                <ul class="analytics-card__list" style="margin:0;">
                    @forelse(collect($customerInsightsData['topCustomers']) as $customer)
                        <li><strong>{{ $customer['name'] }}</strong> – {{ number_format($customer['orders']) }} orders · ₱{{ number_format($customer['total_spent'], 2) }}</li>
                    @empty
                        <li>No customer orders recorded yet.</li>
                    @endforelse
                </ul>
            </div>
        </article>
        <article class="dashboard-card">
            <header class="dashboard-card__header">
                <h2 class="dashboard-card__title">Order Patterns</h2>
                <a href="{{ route('admin.reports.sales') }}" class="pill-link">View analytics</a>
            </header>
            <div>
                <h3 style="margin:0 0 0.5rem 0; font-size:1rem;">Popular themes</h3>
                <ul class="analytics-card__list" style="margin:0;">
                    @forelse($customerInsightsData['popularDesigns'] as $design => $count)
                        <li>{{ $design }} – {{ number_format($count) }} selections</li>
                    @empty
                        <li>No design trends yet.</li>
                    @endforelse
                </ul>
            </div>
            <div style="margin-top:1rem;">
                <h3 style="margin:0 0 0.5rem 0; font-size:1rem;">Peak order dates</h3>
                <ul class="analytics-card__list" style="margin:0;">
                    @forelse(collect($customerInsightsData['peakOrderDays']) as $day)
                        <li>{{ $day['day'] }} – {{ number_format($day['total_orders']) }} orders</li>
                    @empty
                        <li>Order timeline data not available yet.</li>
                    @endforelse
                </ul>
            </div>
        </article>
    </section>


    <section class="dashboard-stock" aria-label="Inventory snapshot">
                <header class="section-header">
                        <div>
                                <h2 class="section-title">Stock Levels</h2>
                                <p class="section-subtitle">Click anywhere on the table to jump to full materials management.</p>
                        </div>
                        <a href="{{ route('admin.materials.index') }}" class="pill-link" aria-label="Open full materials dashboard">View Materials</a>
                </header>

        <div class="table-wrapper">
            <table class="table clickable-table" onclick="window.location='{{ route('admin.materials.index') }}'" role="grid">
                <thead>
                    <tr>
                        <th scope="col">Material</th>
                        <th scope="col">Type</th>
                        <th scope="col">Unit</th>
                        <th scope="col">Stock</th>
                        <th scope="col" class="status-col text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($materials as $material)
                        @php
                            $stock = $material->inventory->stock_level ?? 0;
                            $reorder = $material->inventory->reorder_level ?? 0;
                            $statusClass = 'ok';
                            $statusLabel = 'In Stock';
                            $badgeClass = 'stock-ok';

                            if ($stock <= 0) {
                                $statusClass = 'out';
                                $statusLabel = 'Out of Stock';
                                $badgeClass = 'stock-critical';
                            } elseif ($stock <= $reorder) {
                                $statusClass = 'low';
                                $statusLabel = 'Low Stock';
                                $badgeClass = 'stock-low';
                            }
                        @endphp
                        <tr>
                            <td class="fw-bold">{{ $material->material_name }}</td>
                            <td>{{ $material->material_type }}</td>
                            <td>{{ $material->unit }}</td>
                            <td>
                                <span class="badge {{ $badgeClass }}">{{ $stock }}</span>
                            </td>
                            <td class="text-center">
                                <span class="status-label {{ $statusClass }}">{{ $statusLabel }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">No materials available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="inventory-highlights" aria-label="Inventory alerts and movement">
            <div class="inventory-highlight-card">
                <h3 style="margin:0; font-size:1rem;">Low Stock</h3>
                <p class="section-subtitle" style="margin:0;">Monitor items near reorder level.</p>
                <ul class="analytics-card__list" style="margin:0;">
                    @forelse(collect($inventoryMonitorData['lowStockMaterials'])->take(6) as $material)
                        <li>{{ $material->material_name }} · {{ number_format(optional($material->inventory)->stock_level ?? 0) }} {{ $material->unit }}</li>
                    @empty
                        <li>All materials are above reorder levels.</li>
                    @endforelse
                </ul>
                <a href="{{ route('admin.materials.index') }}" class="pill-link">Open materials</a>
            </div>
            <div class="inventory-highlight-card">
                <h3 style="margin:0; font-size:1rem;">Out of Stock</h3>
                <p class="section-subtitle" style="margin:0;">Resolve before accepting new orders.</p>
                <ul class="analytics-card__list" style="margin:0;">
                    @forelse(collect($inventoryMonitorData['outOfStockMaterials'])->take(6) as $material)
                        <li>{{ $material->material_name }} · {{ $material->unit }}</li>
                    @empty
                        <li>No materials are completely depleted.</li>
                    @endforelse
                </ul>
                <a href="{{ route('admin.materials.index') }}" class="pill-link">Restock now</a>
            </div>
            <div class="inventory-highlight-card">
                <h3 style="margin:0; font-size:1rem;">Moving In / Moving Out</h3>
                <div class="movement-log" aria-label="Recent stock movements">
                    @forelse(collect($inventoryMonitorData['movementLogs']) as $movement)
                        @php
                            $movementType = match($movement->movement_type) {
                                'restock' => 'Moving In',
                                'usage' => 'Moving Out',
                                'adjustment' => 'Adjustment',
                                default => Str::title($movement->movement_type ?? 'update')
                            };
                            $quantity = number_format(abs((int) $movement->quantity));
                            $unit = $movement->material?->unit ?? 'units';
                        @endphp
                        <div class="movement-log__entry">
                            <strong>{{ $movementType }} · {{ $movement->material->material_name ?? 'Unknown material' }}</strong>
                            <div class="movement-log__meta">{{ $quantity }} {{ $unit }} · {{ optional($movement->created_at)->format('M d, Y h:i A') }} · {{ $movement->user->name ?? 'System' }}</div>
                        </div>
                    @empty
                        <p class="analytics-card__empty">No movement logs recorded yet.</p>
                    @endforelse
                </div>
                <a href="{{ route('admin.inventory.index') }}" class="pill-link">Inventory history</a>
            </div>
        </div>
    </section>

    <section class="dashboard-grid" aria-label="Account and system controls">
        <article class="dashboard-card">
            <header class="dashboard-card__header">
                <h2 class="dashboard-card__title">Account &amp; Role Control</h2>
                <a href="{{ route('admin.users.index') }}" class="pill-link">Manage users</a>
            </header>
            <div class="insight-grid">
                <div class="insight-card">
                    <span class="insight-meta-label">Role distribution</span>
                    <ul class="analytics-card__list" style="margin:0;">
                        @forelse($accountControlData['roleBreakdown'] as $role => $total)
                            <li>{{ Str::title($role) }} – {{ number_format($total) }}</li>
                        @empty
                            <li>No users recorded yet.</li>
                        @endforelse
                    </ul>
                </div>
                <div class="insight-card">
                    <span class="insight-meta-label">Staff status</span>
                    <ul class="analytics-card__list" style="margin:0;">
                        @forelse($accountControlData['staffStatusBreakdown'] as $status => $total)
                            <li>{{ Str::title($status ?? 'active') }} – {{ number_format($total) }}</li>
                        @empty
                            <li>No staff records available.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
            <div style="margin-top:1rem;">
                <h3 style="margin:0 0 0.5rem 0; font-size:1rem;">Recently updated staff</h3>
                <div class="activity-feed" style="max-height:200px;">
                    @forelse(collect($accountControlData['recentStaff']) as $staff)
                        <div class="activity-entry">
                            <span><strong>{{ $staff->first_name }} {{ $staff->last_name }}</strong> · {{ Str::title($staff->role ?? 'staff') }}</span>
                            <span class="activity-entry__timestamp">Status: {{ Str::title($staff->status ?? 'active') }} · {{ optional($staff->updated_at)->diffForHumans() }}</span>
                        </div>
                    @empty
                        <p class="analytics-card__empty">No staff updates yet.</p>
                    @endforelse
                </div>
            </div>
        </article>
        <article class="dashboard-card">
            <header class="dashboard-card__header">
                <h2 class="dashboard-card__title">System Controls</h2>
            </header>
            <div class="shortcut-grid">
                @forelse($systemShortcutsData as $shortcut)
                    <a href="{{ $shortcut['route'] }}" class="shortcut-card" style="text-decoration:none; color:inherit;">
                        <span style="font-size:1.5rem;">{{ $shortcut['icon'] }}</span>
                        <strong>{{ $shortcut['label'] }}</strong>
                        <span style="font-size:0.85rem; color:var(--admin-text-secondary);">{{ $shortcut['description'] }}</span>
                    </a>
                @empty
                    <p class="analytics-card__empty">No quick actions configured.</p>
                @endforelse
            </div>
        </article>
    </section>

    <section class="dashboard-grid" aria-label="Activity and scheduling">
        <article class="dashboard-card">
            <header class="dashboard-card__header">
                <h2 class="dashboard-card__title">Recent Activity</h2>
            </header>
            <div class="activity-feed">
                @forelse($recentActivityData as $entry)
                    @php
                        $timestamp = $entry['timestamp'] ?? null;
                        if ($timestamp && !($timestamp instanceof \Carbon\Carbon)) {
                            $timestamp = \Carbon\Carbon::parse($timestamp);
                        }
                    @endphp
                    <div class="activity-entry">
                        <span>{{ $entry['message'] ?? 'Update recorded.' }}</span>
                        <span class="activity-entry__timestamp">
                            {{ optional($timestamp)->format('M d, Y h:i A') }}
                            @if(($entry['type'] ?? null) === 'order' && !empty($entry['order'])) · Order {{ $entry['order'] }} @endif
                            @if(!empty($entry['actor'])) · {{ $entry['actor'] }} @endif
                        </span>
                    </div>
                @empty
                    <p class="analytics-card__empty">No recent actions recorded.</p>
                @endforelse
            </div>
        </article>
        <article class="dashboard-card">
            <header class="dashboard-card__header">
                <h2 class="dashboard-card__title">Upcoming Pickups</h2>
                <a href="{{ $upcomingCalendarData['calendarRoute'] ?? route('admin.reports.pickup-calendar') }}" class="pill-link">Open calendar</a>
            </header>
            @if(!empty($materialAlertsData))
                <span class="section-subtitle">Auto alerts enabled for low stock while orders remain open.</span>
            @endif
            <ul class="analytics-card__list" style="margin:0;">
                @forelse(collect($upcomingCalendarData['upcomingOrders']) as $upcoming)
                    <li>
                        <strong>{{ $upcoming['order_number'] }}</strong>
                        <span> · {{ $upcoming['date_needed'] ?? 'Schedule pending' }}</span>
                        <span> · ₱{{ number_format($upcoming['total_amount'] ?? 0, 2) }}</span>
                        <span> · {{ Str::title(str_replace('_', ' ', $upcoming['status'] ?? 'pending')) }}</span>
                    </li>
                @empty
                    <li>No scheduled pickups yet. Add deadlines via order summary.</li>
                @endforelse
            </ul>
        </article>
    </section>

    <script>
        // Auto-hide greeting after 4 seconds
        setTimeout(() => {
            const greeting = document.getElementById('greetingMessage');
            if (greeting) {
                greeting.style.opacity = '0';
                setTimeout(() => greeting.remove(), 1000);
            }
        }, 4000);

        const initialiseSalesTrendChart = () => {
            const canvas = document.getElementById('salesTrendChart');
            if (!canvas) {
                return;
            }

            const labels = (() => {
                try {
                    return JSON.parse(canvas.dataset.labels || '[]');
                } catch (error) {
                    return [];
                }
            })();

            const values = (() => {
                try {
                    return JSON.parse(canvas.dataset.values || '[]');
                } catch (error) {
                    return [];
                }
            })();

            const numericValues = values.map((value) => {
                const numeric = Number(value);
                return Number.isFinite(numeric) ? numeric : 0;
            });

            if (typeof window.Chart === 'undefined') {
                setTimeout(initialiseSalesTrendChart, 250);
                return;
            }

            if (window.Chart && typeof window.Chart.getChart === 'function') {
                const existingChart = window.Chart.getChart(canvas);
                if (existingChart) {
                    existingChart.destroy();
                }
            }

            new window.Chart(canvas, {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Sales',
                            data: numericValues,
                            borderColor: '#6a2ebc',
                            backgroundColor: 'rgba(106, 46, 188, 0.15)',
                            tension: 0.35,
                            fill: true,
                        },
                    ],
                },
                options: {
                    plugins: {
                        legend: {
                            display: false,
                        },
                    },
                    scales: {
                        y: {
                            ticks: {
                                callback(value) {
                                    return '₱' + Number(value).toLocaleString();
                                },
                            },
                        },
                    },
                },
            });
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initialiseSalesTrendChart);
        } else {
            initialiseSalesTrendChart();
        }
    </script>
</main>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js" defer></script>
@endpush
