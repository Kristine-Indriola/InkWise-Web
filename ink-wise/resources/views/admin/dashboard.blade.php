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
            margin-bottom: 0.9rem;
        }

        .dashboard-page > section:last-of-type {
            margin-bottom: 0;
        }

        /* Improved summary cards styling */
        .summary-grid {
            display: grid;
            gap: 0.55rem;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            margin: 0.85rem 0;
        }

        .summary-card {
            background: var(--admin-surface);
            border-radius: 16px;
            border: 1px solid rgba(148, 185, 255, 0.15);
            padding: 1.05rem;
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
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
            padding: 14px;
            display: grid;
            gap: 0.75rem;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            width: 100%;
        }

        .analytics-grid {
            display: grid;
            gap: 0.65rem;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            justify-items: stretch;
            align-items: stretch;
        }

        .analytics-card--compact {
            padding: 0.9rem;
            gap: 0.6rem;
        }

        .chart-grid {
            display: grid;
            gap: 0.55rem;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            margin: 1rem 0;
            justify-items: stretch;
            align-items: stretch;
        }

        .chart-card {
            background: var(--admin-surface);
            border-radius: 18px;
            border: 1px solid rgba(148, 185, 255, 0.18);
            box-shadow: var(--admin-shadow-soft);
            padding: 0.95rem;
            display: grid;
            gap: 0.55rem;
            position: relative;
            overflow: hidden;
            width: 100%;
        }

        .chart-card::after {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at top right, rgba(106, 46, 188, 0.12), transparent 65%);
            opacity: 0;
            transition: opacity 0.2s ease;
            pointer-events: none;
        }

        .chart-card:hover::after {
            opacity: 1;
        }

        .chart-card__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .chart-card__title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--admin-text-primary);
        }

        .chart-card__meta {
            font-size: 0.82rem;
            color: var(--admin-text-secondary);
        }

        .chart-card__empty {
            margin: 0;
            font-size: 0.88rem;
            color: var(--admin-text-secondary);
        }

        .chart-card canvas {
            display: block;
            width: 100%;
            height: auto;
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

        .analytics-card__list--dense {
            gap: 4px;
            font-size: 0.92rem;
        }

        .insights-grid {
            display: grid;
            gap: 0.6rem;
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

        .insight-chart {
            border-radius: 16px;
            border: 1px solid rgba(148, 185, 255, 0.18);
            background: rgba(148, 185, 255, 0.08);
            padding: 1rem;
            display: grid;
            gap: 0.4rem;
            justify-items: center;
        }

        .insight-chart--slim {
            padding: 0.75rem;
            gap: 0.3rem;
        }

        .insight-chart canvas {
            display: block;
            width: 100%;
            height: auto;
        }

        .insight-chart-grid {
            display: grid;
            gap: 0.6rem;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            justify-items: stretch;
        }

        .insight-chart--compact {
            max-width: 280px;
            width: 100%;
            margin: 0 auto;
        }

        .behavior-chart-row {
            display: flex;
            gap: 0.75rem;
            flex-direction: row;
            flex-wrap: wrap;
        }

        .behavior-chart {
            display: flex;
            flex-direction: column;
            gap: 0.45rem;
        }

        .behavior-chart .insight-chart {
            flex: 1;
            min-height: 220px;
        }

        .insight-chart--medium {
            max-width: 480px;
            width: 100%;
            margin: 0 auto;
        }

        .insight-chart--wide {
            max-width: 520px;
            width: 100%;
            margin: 0 auto;
        }

        .design-highlight__cta {
            margin-top: auto;
            width: fit-content;
        }

        .design-highlight__chart {
            margin-top: 0.75rem;
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
            gap: 0.6rem;
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
            gap: 0.6rem;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            max-height: 260px;
            overflow-y: auto;
            padding-right: 4px;
        }

        .review-card {
            border-radius: 16px;
            border: 1px solid rgba(148, 185, 255, 0.18);
            background: rgba(148, 185, 255, 0.08);
            padding: 1rem;
            display: grid;
            gap: 0.65rem;
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
            padding: 20px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            width: 100%;
        }

        .dashboard-stock:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 0.65rem;
            margin-bottom: 0.9rem;
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
            gap: 0.6rem;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            margin: 1.3rem 0;
        }

        .overview-card {
            background: var(--admin-surface);
            border: 1px solid rgba(148, 185, 255, 0.12);
            border-radius: 16px;
            padding: 1.05rem;
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
            gap: 0.7rem;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            align-items: stretch;
        }

        .dashboard-grid--single {
            grid-template-columns: minmax(0, 1fr);
        }

        .dashboard-grid--single .dashboard-card {
            max-width: 1200px;
            margin: 0 auto;
        }

        .dashboard-grid--compact {
            justify-content: start;
        }

        .dashboard-card {
            background: var(--admin-surface);
            border: 1px solid rgba(148, 185, 255, 0.18);
            border-radius: 18px;
            padding: 0.9rem;
            box-shadow: var(--admin-shadow-soft);
            display: grid;
            gap: 0.7rem;
            width: 100%;
        }

        .dashboard-card--slim {
            padding: 0.65rem;
            gap: 0.55rem;
        }

        .dashboard-card--slim .dashboard-card__title {
            font-size: 1.05rem;
        }

        .dashboard-card--slim .insight-grid {
            gap: 0.45rem;
        }

        .dashboard-card--wide {
            grid-column: 1 / -1;
            width: 100%;
        }

        .dashboard-card--narrow {
            width: 100%;
            justify-self: start;
        }

        .dashboard-card__header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 0.7rem;
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
            gap: 0.75rem;
        }

        .order-item {
            border: 1px solid rgba(148, 185, 255, 0.12);
            border-radius: 14px;
            padding: 0.9rem;
            display: grid;
            gap: 0.6rem;
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
            gap: 0.75rem;
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
            gap: 0.6rem;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        }

        .sales-metric {
            background: rgba(148, 185, 255, 0.08);
            border-radius: 12px;
            padding: 0.75rem;
            border: 1px solid rgba(148, 185, 255, 0.12);
        }

        .sales-metric strong {
            display: block;
            font-size: 1.2rem;
            margin-top: 0.25rem;
        }

        .sales-chart-wrapper {
            position: relative;
            min-height: 180px;
        }

        .sales-summary-chart {
            margin-top: 0.7rem;
            border-radius: 16px;
            border: 1px solid rgba(148, 185, 255, 0.18);
            background: rgba(148, 185, 255, 0.08);
            padding: 0.65rem;
        }

        .sales-summary-chart canvas {
            width: 100% !important;
            height: 140px !important;
        }

        .sales-performance-grid {
            display: grid;
            gap: 0.6rem;
            grid-template-columns: minmax(200px, 1.25fr) minmax(180px, 1fr);
            align-items: start;
        }

        .sales-performance-sidebar {
            display: grid;
            gap: 0.6rem;
        }

        @media (max-width: 960px) {
            .sales-performance-grid {
                grid-template-columns: 1fr;
            }
        }

        .insight-grid {
            display: grid;
            gap: 0.75rem;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        }

        .insight-grid--stacked {
            grid-template-columns: minmax(0, 1fr);
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
            gap: 0.75rem;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
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
            gap: 0.6rem;
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
            gap: 0.75rem;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            margin-bottom: 1.2rem;
        }

        .inventory-highlight-card {
            border: 1px solid rgba(148, 185, 255, 0.18);
            border-radius: 14px;
            padding: 0.9rem;
            background: rgba(148, 185, 255, 0.06);
            display: grid;
            gap: 0.5rem;
        }

        .movement-log {
            display: grid;
            gap: 0.5rem;
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
            padding: 0.9rem 1.1rem;
            display: grid;
            gap: 0.35rem;
            margin-bottom: 1rem;
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
            padding: 1.05rem;
            display: grid;
            gap: 0.6rem;
        }

        .announcement-board__item {
            background: rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            padding: 0.7rem;
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

        /* Stock Status card styles */
        .stock-status-content {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .stock-chart-section {
            width: 100%;
        }

        .stock-alerts-section {
            width: 100%;
        }

        .stock-alerts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .stock-alert {
            background: var(--admin-surface);
            border: 1px solid rgba(148, 185, 255, 0.15);
            border-radius: 8px;
            padding: 1rem;
        }

        /* Demand Analysis card styles */
        .demand-analysis-content {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .demand-chart-section {
            width: 100%;
        }

        .demand-insights-section {
            width: 100%;
        }

        .demand-insights-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.75rem;
        }

        .demand-insight-card {
            background: var(--admin-surface);
            border: 1px solid rgba(148, 185, 255, 0.15);
            border-radius: 12px;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .demand-insight-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .demand-insight-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .demand-insight-icon {
            font-size: 1.2rem;
        }

        .demand-insight-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--admin-text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .demand-insight-value {
            font-size: 1rem;
            font-weight: 700;
            color: var(--admin-text-primary);
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .demand-insight-subtext {
            font-size: 0.7rem;
            font-weight: 500;
            color: var(--admin-text-secondary);
        }

        /* Popular Designs styles */
        .popular-designs-content {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .popular-designs-chart-section {
            width: 100%;
        }

        .popular-designs-grid-section {
            width: 100%;
        }

        .popular-designs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 0.75rem;
        }

        .popular-design-item {
            background: var(--admin-surface);
            border: 1px solid rgba(148, 185, 255, 0.15);
            border-radius: 12px;
            padding: 0.75rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .popular-design-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .popular-design-image {
            width: 100%;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            background: var(--admin-surface);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .popular-design-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .no-image-placeholder {
            font-size: 2rem;
            color: var(--admin-text-secondary);
        }

        .popular-design-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .popular-design-name {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--admin-text-primary);
            line-height: 1.2;
        }

        .popular-design-count {
            font-size: 0.7rem;
            color: var(--admin-text-secondary);
            font-weight: 500;
        }

        .popular-design-item--empty {
            opacity: 0.6;
        }

        /* Calendar styles for upcoming pickups */
        .pickup-calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .calendar-day {
            background: var(--admin-surface);
            border: 1px solid rgba(148, 185, 255, 0.15);
            border-radius: 12px;
            padding: 0.75rem;
            display: flex;
            flex-direction: column;
            min-height: 120px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .calendar-day:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .calendar-day.today {
            border-color: #6a2ebc;
            background: linear-gradient(135deg, rgba(106, 46, 188, 0.05), rgba(60, 213, 200, 0.05));
        }

        .calendar-day.has-pickups {
            border-color: rgba(106, 46, 188, 0.3);
        }

        .calendar-day-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .day-name {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--admin-text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .day-number {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--admin-text-primary);
            margin-top: 0.25rem;
        }

        .calendar-day.today .day-number {
            color: #6a2ebc;
        }

        .calendar-day-pickups {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .pickup-item {
            background: rgba(106, 46, 188, 0.08);
            border-radius: 6px;
            padding: 0.4rem;
            font-size: 0.75rem;
        }

        .pickup-order {
            font-weight: 600;
            color: var(--admin-text-primary);
            margin-bottom: 0.2rem;
        }

        .pickup-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pickup-amount {
            font-weight: 600;
            color: #3cd5c8;
        }

        .pickup-status {
            font-size: 0.65rem;
            padding: 0.15rem 0.4rem;
            border-radius: 10px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .pickup-status.pending {
            background: rgba(255, 193, 7, 0.2);
            color: #856404;
        }

        .pickup-status.confirmed {
            background: rgba(40, 167, 69, 0.2);
            color: #155724;
        }

        .pickup-status.completed {
            background: rgba(23, 162, 184, 0.2);
            color: #0c5460;
        }

        .pickup-status.cancelled {
            background: rgba(220, 53, 69, 0.2);
            color: #721c24;
        }

        .no-pickups {
            font-size: 0.7rem;
            color: var(--admin-text-secondary);
            font-style: italic;
            text-align: center;
            margin-top: auto;
        }

        .unscheduled-pickups {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(148, 185, 255, 0.15);
        }

        .unscheduled-pickups h4 {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--admin-text-primary);
            margin-bottom: 0.5rem;
        }

        @media (max-width: 768px) {
            .pickup-calendar {
                grid-template-columns: repeat(3, 1fr);
                gap: 0.4rem;
            }

            .calendar-day {
                min-height: 100px;
                padding: 0.5rem;
            }

            .day-number {
                font-size: 1rem;
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
    $salesPreviewData = $salesPreview ?? [
        'daily' => 0.0,
        'weekly' => 0.0,
        'monthly' => 0.0,
        'trend' => ['labels' => [], 'values' => []],
        'bestSelling' => collect(),
        'recentTransactions' => collect(),
        'periodDetails' => [],
        'yearToDate' => [
            'current' => ['sales' => 0.0, 'orders' => 0, 'average' => 0.0],
            'previous' => ['sales' => 0.0, 'orders' => 0, 'average' => 0.0],
            'salesDelta' => null,
            'ordersDelta' => null,
        ],
        'paymentMethodBreakdown' => [],
    ];
    $inventoryMonitorData = $inventoryMonitor ?? ['lowStockMaterials' => collect(), 'outOfStockMaterials' => collect(), 'movementLogs' => collect()];
    $customerInsightsData = $customerInsights ?? [
        'topCustomers' => collect(),
        'repeatCustomers' => 0,
        'popularDesigns' => [],
        'peakOrderDays' => collect(),
        'orderFrequency' => [
            'averagePerCustomer' => 0,
            'averageGapDays' => null,
            'orderCount' => 0,
            'customerCount' => 0,
            'window' => ['start' => null, 'end' => null],
            'windowLabel' => null,
        ],
        'timeOfDayBuckets' => [],
        'dayOfWeekBreakdown' => [],
    ];
    $systemShortcutsData = $systemShortcuts ?? [];
    $recentActivityData = collect($recentActivityFeed ?? []);
    $upcomingCalendarData = $upcomingCalendar ?? ['upcomingOrders' => collect(), 'calendarRoute' => route('admin.reports.pickup-calendar')];
    $materialAlertsData = $materialAlerts ?? [];
    $announcements = $dashboardAnnouncements ?? [];
    $reviewSnapshot = $customerReviewSnapshot ?? ['average' => null, 'count' => 0];

    $repeatCustomersCount = (int) ($customerInsightsData['repeatCustomers'] ?? 0);
    $totalCustomersCount = (int) ($overview['totalCustomers'] ?? 0);
    $newCustomersCount = max($totalCustomersCount - $repeatCustomersCount, 0);
    $repeatVsNewChart = [
        'labels' => ['Repeat customers', 'New customers'],
        'values' => [$repeatCustomersCount, $newCustomersCount],
    ];
    $repeatVsNewHasData = array_sum($repeatVsNewChart['values']) > 0;

    $popularDesignsData = collect($customerInsightsData['popularDesigns'] ?? []);
    $popularDesignsChart = [
        'labels' => $popularDesignsData->pluck('product_name')->map(fn ($label) => Str::limit((string) $label, 26))->values()->all(),
        'values' => $popularDesignsData->pluck('orders')->map(fn ($value) => (int) $value)->values()->all(),
    ];
    $popularDesignsHasData = collect($popularDesignsChart['values'])->sum() > 0;

    // Enhanced popular designs with real image data from controller
    $popularDesignsWithImages = $popularDesignsData->take(6)->map(function ($design) {
        return [
            'name' => $design['product_name'] ?? 'Unknown Product',
            'count' => $design['orders'] ?? 0,
            'image' => $design['image'] ?? null,
            'short_name' => Str::limit($design['product_name'] ?? 'Unknown Product', 20)
        ];
    });

    $peakOrderDaysData = collect($customerInsightsData['peakOrderDays'] ?? []);
    $peakOrderDaysChart = [
        'labels' => $peakOrderDaysData->pluck('day')->map(fn ($day) => Str::limit((string) $day, 20))->values()->all(),
        'values' => $peakOrderDaysData->pluck('total_orders')->map(fn ($value) => (int) $value)->values()->all(),
    ];
    $peakOrderDaysHasData = collect($peakOrderDaysChart['values'])->sum() > 0;

    $orderFrequencyStats = $customerInsightsData['orderFrequency'] ?? [];
    $timeOfDayData = collect($customerInsightsData['timeOfDayBuckets'] ?? []);
    $timeOfDayChart = [
        'labels' => $timeOfDayData->keys()->map(fn ($label) => Str::title((string) $label))->values()->all(),
        'values' => $timeOfDayData->values()->map(fn ($value) => (int) $value)->values()->all(),
    ];
    $timeOfDayHasData = collect($timeOfDayChart['values'])->sum() > 0;

    $dayOfWeekData = collect($customerInsightsData['dayOfWeekBreakdown'] ?? []);
    $dayOfWeekChart = [
        'labels' => $dayOfWeekData->pluck('label')->values()->all(),
        'values' => $dayOfWeekData->pluck('total')->map(fn ($value) => (int) $value)->values()->all(),
    ];
    $dayOfWeekHasData = collect($dayOfWeekChart['values'])->sum() > 0;

    $yearToDateSummary = $salesPreviewData['yearToDate'] ?? [
        'current' => ['sales' => 0.0, 'orders' => 0, 'average' => 0.0],
        'previous' => ['sales' => 0.0, 'orders' => 0, 'average' => 0.0],
        'salesDelta' => null,
        'ordersDelta' => null,
    ];
    $yearToDateCurrent = $yearToDateSummary['current'] ?? ['sales' => 0.0, 'orders' => 0, 'average' => 0.0];
    $yearToDatePrevious = $yearToDateSummary['previous'] ?? ['sales' => 0.0, 'orders' => 0, 'average' => 0.0];
    $yearToDateSalesDelta = $yearToDateSummary['salesDelta'] ?? null;
    $yearToDateOrdersDelta = $yearToDateSummary['ordersDelta'] ?? null;

    $ratingDistribution = \App\Models\OrderRating::select('rating', \DB::raw('COUNT(*) as total'))
        ->groupBy('rating')
        ->orderBy('rating', 'asc')
        ->pluck('total', 'rating');
    $ratingScale = range(5, 1);
    $ratingDistributionChart = [
        'labels' => array_map(fn ($rating) => $rating . '★', $ratingScale),
        'values' => array_map(fn ($rating) => (int) ($ratingDistribution[$rating] ?? 0), $ratingScale),
    ];
    $ratingDistributionHasData = array_sum($ratingDistributionChart['values']) > 0;

    $reviewResponseCounts = \App\Models\OrderRating::selectRaw('CASE WHEN staff_reply IS NULL THEN "Pending" ELSE "Responded" END as state, COUNT(*) as total')
        ->groupBy('state')
        ->pluck('total', 'state');
    $reviewResponseChart = [
        'labels' => ['Responded', 'Pending'],
        'values' => [
            (int) ($reviewResponseCounts['Responded'] ?? 0),
            (int) ($reviewResponseCounts['Pending'] ?? 0),
        ],
    ];
    $reviewResponseHasData = array_sum($reviewResponseChart['values']) > 0;

    $totalSkus = (int) ($metrics['totalSkus'] ?? 0);
    $lowStockCount = (int) ($metrics['lowStock'] ?? 0);
    $outStockCount = (int) ($metrics['outOfStock'] ?? 0);
    $healthySkus = max($totalSkus - $lowStockCount - $outStockCount, 0);
    $stockLevelsChart = [
        'labels' => ['Healthy SKUs', 'Low Stock', 'Out of Stock'],
        'values' => [$healthySkus, $lowStockCount, $outStockCount],
    ];
    $stockLevelsHasData = array_sum($stockLevelsChart['values']) > 0;

    $criticalMaterials = collect($inventoryMonitorData['lowStockMaterials'] ?? [])
        ->merge(collect($inventoryMonitorData['outOfStockMaterials'] ?? []))
        ->unique('material_id')
        ->take(6);
    $criticalMaterialsChart = [
        'labels' => $criticalMaterials->map(fn ($material) => Str::limit((string) ($material->material_name ?? 'Material'), 24))->values()->all(),
        'values' => $criticalMaterials->map(function ($material) {
            $stock = (int) (optional($material->inventory)->stock_level ?? $material->stock_qty ?? 0);
            $reorder = (int) (optional($material->inventory)->reorder_level ?? $material->reorder_point ?? 0);
            return max($reorder - $stock, 0);
        })->values()->all(),
    ];
    $criticalMaterialsHasData = collect($criticalMaterialsChart['values'])->sum() > 0;

    $materialStockSnapshot = collect($materials ?? [])
        ->filter()
        ->map(function ($material) {
            $stock = (int) (optional($material->inventory)->stock_level ?? $material->stock_qty ?? 0);
            $reorder = (int) (optional($material->inventory)->reorder_level ?? $material->reorder_point ?? 0);
            $status = 'healthy';
            if ($stock <= 0) {
                $status = 'out';
            } elseif ($stock <= $reorder) {
                $status = 'low';
            }

            return [
                'name' => (string) ($material->material_name ?? 'Material'),
                'unit' => (string) ($material->unit ?? 'units'),
                'stock' => $stock,
                'status' => $status,
            ];
        })
        ->sortByDesc(fn ($material) => $material['stock'])
        ->take(10)
        ->values();

    $materialStockChart = [
        'labels' => $materialStockSnapshot->map(fn ($material) => Str::limit($material['name'], 28))->all(),
        'values' => $materialStockSnapshot->map(fn ($material) => $material['stock'])->all(),
        'units' => $materialStockSnapshot->map(fn ($material) => $material['unit'])->all(),
        'colors' => $materialStockSnapshot->map(function ($material) {
            return match ($material['status']) {
                'out' => 'rgba(239, 68, 68, 0.75)',
                'low' => 'rgba(245, 158, 11, 0.75)',
                default => 'rgba(58, 133, 244, 0.75)',
            };
        })->all(),
    ];
    $materialStockHasData = collect($materialStockChart['values'])->sum() > 0;

    // Demand Analysis Data - Mock data for demonstration
    $demandTrendsChart = [
        'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
        'datasets' => [
            [
                'label' => 'Material Usage',
                'data' => [120, 150, 180, 140, 200, 170],
                'borderColor' => '#6a2ebc',
                'backgroundColor' => 'rgba(106, 46, 188, 0.1)',
                'tension' => 0.4,
                'fill' => true,
            ],
            [
                'label' => 'Predicted Demand',
                'data' => [130, 160, 175, 155, 190, 185],
                'borderColor' => '#3cd5c8',
                'backgroundColor' => 'rgba(60, 213, 200, 0.1)',
                'borderDash' => [5, 5],
                'tension' => 0.4,
                'fill' => false,
            ]
        ]
    ];

    $demandInsights = [
        'peakPeriod' => [
            'period' => 'March',
            'usage' => 180
        ],
        'lowPeriod' => [
            'period' => 'April',
            'usage' => 140
        ],
        'recommendedReorder' => 160,
        'trend' => 'increasing',
        'trendPercent' => 8.3
    ];
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
    @php
        $statusOptionsMap = collect($orderManagementData['statusOptions'] ?? []);
        $orderStatusCounts = collect($orderManagementData['statusCounts'] ?? []);
        $orderStatusLabels = $orderStatusCounts
            ->keys()
            ->map(function ($status) use ($statusOptionsMap) {
                $resolved = $statusOptionsMap[$status] ?? Str::title(str_replace('_', ' ', (string) $status));
                return Str::limit($resolved, 32);
            })
            ->values();
        $orderStatusValues = $orderStatusCounts
            ->values()
            ->map(fn ($value) => (int) $value)
            ->values();
        $orderStatusChart = [
            'labels' => $orderStatusLabels->all(),
            'values' => $orderStatusValues->all(),
        ];
        $orderStatusHasData = $orderStatusValues->sum() > 0;

        $paymentStatusCounts = collect($orderManagementData['paymentStatusCounts'] ?? []);
        $paymentStatusLabels = $paymentStatusCounts
            ->keys()
            ->map(fn ($status) => Str::title(str_replace('_', ' ', (string) $status)))
            ->values();
        $paymentStatusValues = $paymentStatusCounts
            ->values()
            ->map(fn ($value) => (int) $value)
            ->values();
        $paymentStatusChart = [
            'labels' => $paymentStatusLabels->all(),
            'values' => $paymentStatusValues->all(),
        ];
        $paymentStatusHasData = $paymentStatusValues->sum() > 0;

        $inventoryChart = [
            'labels' => ['Healthy Stock', 'Low Stock', 'Out of Stock'],
            'values' => [$healthySkus, $lowStockCount, $outStockCount],
        ];
        $inventoryHasData = array_sum($inventoryChart['values']) > 0;

        $topCustomersForChart = collect($customerInsightsData['topCustomers'] ?? [])->take(5);
        $topCustomersChart = [
            'labels' => $topCustomersForChart->pluck('name')->map(fn ($name) => Str::limit((string) $name, 28))->values()->all(),
            'values' => $topCustomersForChart->pluck('total_spent')->map(fn ($value) => round((float) $value, 2))->values()->all(),
            'orders' => $topCustomersForChart->pluck('orders')->map(fn ($value) => (int) $value)->values()->all(),
        ];
        $topCustomersHasData = collect($topCustomersChart['values'])->sum() > 0 || collect($topCustomersChart['orders'])->sum() > 0;
    @endphp

    <section class="dashboard-grid" aria-label="System controls">
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
    </section>
                @endforelse
            </div>
        </article>
    </section>

    <section class="chart-grid" aria-label="Key operational charts">
        <article class="chart-card">
            <header class="chart-card__header">
                <h2 class="chart-card__title">Order Pipeline Status</h2>
                <span class="chart-card__meta">Live distribution of active orders</span>
            </header>
            <div class="insight-chart insight-chart--medium">
                <canvas id="orderStatusChart" data-chart='@json($orderStatusChart)'></canvas>
                <p id="orderStatusChartEmpty" class="chart-card__empty" @if($orderStatusHasData) hidden @endif>No order status data to visualise yet.</p>
            </div>
        </article>
        <article class="chart-card">
            <header class="chart-card__header">
                <h2 class="chart-card__title">Payment Progress</h2>
                <span class="chart-card__meta">Shows clearance across payment states</span>
            </header>
            <div class="insight-chart insight-chart--medium">
                <canvas id="paymentStatusChart" data-chart='@json($paymentStatusChart)'></canvas>
                <p id="paymentStatusChartEmpty" class="chart-card__empty" @if($paymentStatusHasData) hidden @endif>No payment state data available.</p>
            </div>
        </article>
        <article class="chart-card">
            <header class="chart-card__header">
                <h2 class="chart-card__title">Inventory Mix</h2>
                <span class="chart-card__meta">SKU health by stock coverage</span>
            </header>
            <div class="insight-chart insight-chart--compact">
                <canvas id="inventoryMixChart" data-chart='@json($inventoryChart)'></canvas>
                <p id="inventoryMixChartEmpty" class="chart-card__empty" @if($inventoryHasData) hidden @endif>No inventory metrics recorded yet.</p>
            </div>
        </article>
        <article class="chart-card">
            <header class="chart-card__header">
                <h2 class="chart-card__title">Top Customers</h2>
                <span class="chart-card__meta">Ranked by spend and repeat orders</span>
            </header>
            <div class="insight-chart insight-chart--wide">
                <canvas id="topCustomersChart" data-chart='@json($topCustomersChart)'></canvas>
                <p id="topCustomersChartEmpty" class="chart-card__empty" @if($topCustomersHasData) hidden @endif>Customer insights will appear once orders are completed.</p>
            </div>
        </article>
    </section>

    @php
        $recentReviews = \App\Models\OrderRating::with(['customer', 'order'])
            ->latest('submitted_at')
            ->take(4)
            ->get();
        $outstandingReviews = \App\Models\OrderRating::whereNull('staff_reply')->count();
        $roundedAverageRating = $reviewSnapshot['average'] ? round($reviewSnapshot['average'], 1) : null;
    @endphp

    {{-- Sales and inventory insights section removed per user request --}}

    <section class="dashboard-grid" aria-label="Sales performance">
        @php
            $bestSellingProducts = collect($salesPreviewData['bestSelling'] ?? [])->take(3);
            $recentTransactions = collect($salesPreviewData['recentTransactions'] ?? [])->take(3);
        @endphp
        <article class="dashboard-card">
            <header class="dashboard-card__header">
                <h2 class="dashboard-card__title">Weekly Sales Trends</h2>
            </header>
            <div class="sales-chart-wrapper" role="img" aria-label="Weekly sales trend chart">
                <span class="insight-meta-label">Weekly revenue (completed orders)</span>
                <canvas id="salesTrendChart"
                    data-labels='@json($salesPreviewData['trend']['labels'] ?? [])'
                    data-values='@json($salesPreviewData['trend']['values'] ?? [])'>
                </canvas>
            </div>
        </article>
        <article class="dashboard-card">
            <header class="dashboard-card__header">
                <h2 class="dashboard-card__title">Year-to-Date Performance</h2>
                <a href="{{ route('admin.reports.sales') }}" class="pill-link">View sales report</a>
            </header>
            <div class="insight-grid">
                <div class="insight-card">
                    <span class="insight-meta-label">Revenue</span>
                    <span class="insight-meta-value">₱{{ number_format($yearToDateCurrent['sales'] ?? 0, 2) }}</span>
                    <span class="insight-meta-caption">Prior: ₱{{ number_format($yearToDatePrevious['sales'] ?? 0, 2) }}</span>
                    @if(is_array($yearToDateSalesDelta))
                        @php
                            $ytdSalesDirection = $yearToDateSalesDelta['direction'] ?? 'flat';
                            $ytdSalesClass = 'insight-delta insight-delta--flat';
                            $ytdSalesSymbol = '•';
                            if ($ytdSalesDirection === 'up') {
                                $ytdSalesClass = 'insight-delta insight-delta--up';
                                $ytdSalesSymbol = '▲';
                            } elseif ($ytdSalesDirection === 'down') {
                                $ytdSalesClass = 'insight-delta insight-delta--down';
                                $ytdSalesSymbol = '▼';
                            }
                            $ytdSalesPercent = abs((float) ($yearToDateSalesDelta['percent'] ?? 0));
                        @endphp
                        <span class="{{ $ytdSalesClass }}" title="Revenue change vs prior YTD">
                            <span class="insight-delta__icon">{{ $ytdSalesSymbol }}</span>{{ number_format($ytdSalesPercent, 1) }}%
                        </span>
                    @endif
                </div>
                <div class="insight-card">
                    <span class="insight-meta-label">Orders</span>
                    <span class="insight-meta-value">{{ number_format($yearToDateCurrent['orders'] ?? 0) }}</span>
                    <span class="insight-meta-caption">Prior: {{ number_format($yearToDatePrevious['orders'] ?? 0) }}</span>
                    @if(is_array($yearToDateOrdersDelta))
                        @php
                            $ytdOrdersDirection = $yearToDateOrdersDelta['direction'] ?? 'flat';
                            $ytdOrdersClass = 'insight-delta insight-delta--flat';
                            $ytdOrdersSymbol = '•';
                            if ($ytdOrdersDirection === 'up') {
                                $ytdOrdersClass = 'insight-delta insight-delta--up';
                                $ytdOrdersSymbol = '▲';
                            } elseif ($ytdOrdersDirection === 'down') {
                                $ytdOrdersClass = 'insight-delta insight-delta--down';
                                $ytdOrdersSymbol = '▼';
                            }
                            $ytdOrdersPercent = abs((float) ($yearToDateOrdersDelta['percent'] ?? 0));
                        @endphp
                        <span class="{{ $ytdOrdersClass }}" title="Order change vs prior YTD">
                            <span class="insight-delta__icon">{{ $ytdOrdersSymbol }}</span>{{ number_format($ytdOrdersPercent, 1) }}%
                        </span>
                    @endif
                </div>
                <div class="insight-card">
                    <span class="insight-meta-label">Average order value</span>
                    <span class="insight-meta-value">₱{{ number_format($yearToDateCurrent['average'] ?? 0, 2) }}</span>
                    <span class="insight-meta-caption">Prior: ₱{{ number_format($yearToDatePrevious['average'] ?? 0, 2) }}</span>
                </div>
            </div>
        </article>
        <article class="dashboard-card">
            <header class="dashboard-card__header">
                <h2 class="dashboard-card__title">Top Products This Week</h2>
            </header>
            <ul class="analytics-card__list" style="margin:0;">
                @forelse($bestSellingProducts as $product)
                    @php
                        $productImage = $product->image_url ?? \App\Support\ImageResolver::url(null);
                    @endphp
                    <li>
                        <div style="display:flex; align-items:center; gap:0.75rem;">
                            <div style="width:56px; height:56px; border-radius:0.75rem; overflow:hidden; background:#f3f4f6; border:1px solid rgba(15, 23, 42, 0.08); flex-shrink:0;">
                                <img src="{{ $productImage }}" alt="{{ $product->label }} preview" style="width:100%; height:100%; object-fit:cover;">
                            </div>
                            <div>
                                <strong>{{ $product->label }}</strong> – ₱{{ number_format($product->total_revenue ?? 0, 2) }}
                                <span class="insight-meta-caption" style="display:block;">{{ number_format($product->orders_count ?? 0) }} orders · {{ number_format($product->quantity ?? 0) }} units</span>
                            </div>
                        </div>
                    </li>
                @empty
                    <li>No product performance data yet.</li>
                @endforelse
            </ul>
        </article>
        <article class="dashboard-card">
            <header class="dashboard-card__header">
                <h2 class="dashboard-card__title">Recent Transactions</h2>
                <a href="{{ route('admin.payments.index') }}" class="pill-link">View payments</a>
            </header>
            <ul class="analytics-card__list" style="margin:0;">
                @forelse($recentTransactions as $transaction)
                    @php
                        $transactionLabel = $transaction->reference ?? $transaction->order?->order_number ?? ('Payment #' . ($transaction->id ?? ''));
                        $transactionAmount = $transaction->amount ?? $transaction->total ?? 0;
                        $transactionStatus = Str::title(str_replace('_', ' ', $transaction->status ?? 'completed'));
                        $transactionTimestamp = $transaction->recorded_at ?? $transaction->created_at;
                        $transactionTime = $transactionTimestamp ? optional($transactionTimestamp)->diffForHumans() : 'Just now';
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
        </article>
    </section>

            <header class="section-header">
            <div>
                <h2 class="section-title">Customer Behavior Insights</h2>
                <p class="section-subtitle">Track popular designs, ordering cadence, and repeat buying signals.</p>
            </div>
        </header>

    <section class="dashboard-grid" aria-label="Customer insights">
        @php
            $averageOrdersPerCustomer = (float) ($orderFrequencyStats['averagePerCustomer'] ?? 0);
            $averageGapDays = $orderFrequencyStats['averageGapDays'] ?? null;
            $frequencyWindowLabel = $orderFrequencyStats['windowLabel'] ?? null;
            $frequencyOrderCount = (int) ($orderFrequencyStats['orderCount'] ?? 0);
            $frequencyCustomerCount = (int) ($orderFrequencyStats['customerCount'] ?? 0);
            $timeOfDayTop = $timeOfDayData->filter(fn ($value) => $value > 0)->sortDesc()->keys()->first();
            $dayOfWeekTop = $dayOfWeekData->sortByDesc('total')->first();
        @endphp


        <article class="dashboard-card">
            <header class="dashboard-card__header">
                <h2 class="dashboard-card__title">Popular design selections</h2>
                <a href="{{ route('admin.customers.index') }}" class="pill-link">View customers</a>
            </header>
            <div class="popular-designs-content">
                <div class="popular-designs-chart-section">
                    <div class="behavior-chart">
                        <div class="insight-chart insight-chart--compact insight-chart--slim" style="margin:0;">
                            <span class="insight-meta-label">Popular design selections</span>
                            <canvas id="popularDesignsMiniChart" data-chart='@json($popularDesignsChart)'></canvas>
                            <p id="popularDesignsMiniEmpty" class="chart-card__empty" @if($popularDesignsHasData) hidden @endif>Popular designs populate once orders accumulate.</p>
                        </div>
                    </div>
                </div>
                <div class="popular-designs-grid-section">
                    <h4 style="margin:0 0 0.75rem 0; font-size:0.9rem; font-weight:600;">Top Designs</h4>
                    <div class="popular-designs-grid">
                        @forelse($popularDesignsWithImages as $design)
                            <div class="popular-design-item">
                                <div class="popular-design-image">
                                    @if($design['image'])
                                        <img src="{{ $design['image'] }}" alt="{{ $design['name'] }}" loading="lazy" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="no-image-placeholder" style="display:none;">📋</div>
                                    @else
                                        <div class="no-image-placeholder">📋</div>
                                    @endif
                                </div>
                                <div class="popular-design-info">
                                    <div class="popular-design-name">{{ $design['short_name'] }}</div>
                                    <div class="popular-design-count">{{ number_format($design['count']) }} selections</div>
                                </div>
                            </div>
                        @empty
                            <div class="popular-design-item popular-design-item--empty">
                                <div class="popular-design-image">
                                    <div class="no-image-placeholder">📋</div>
                                </div>
                                <div class="popular-design-info">
                                    <div class="popular-design-name">No designs yet</div>
                                    <div class="popular-design-count">Orders needed</div>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </article>
        <article class="dashboard-card">
            <header class="dashboard-card__header">
                <h2 class="dashboard-card__title">Repeat vs new mix</h2>
            </header>
            <div class="behavior-chart">
                <div class="insight-chart insight-chart--compact insight-chart--slim" style="margin:0;">
                    <span class="insight-meta-label">Repeat vs new mix</span>
                    <canvas id="repeatCustomersSplitChart" data-chart='@json($repeatVsNewChart)'></canvas>
                    <p id="repeatCustomersSplitEmpty" class="chart-card__empty" @if($repeatVsNewHasData) hidden @endif>Customer mix appears when orders complete.</p>
                </div>
            </div>
        </article>
        <article class="dashboard-card">
            <header class="dashboard-card__header">
                <h2 class="dashboard-card__title">Ordering cadence by time</h2>
            </header>
            <div class="behavior-chart">
                <div class="insight-chart insight-chart--compact insight-chart--slim" style="margin:0;">
                    <span class="insight-meta-label">Ordering cadence by time</span>
                    <canvas id="timeOfDayOrdersChart" data-chart='@json($timeOfDayChart)'></canvas>
                    <p id="timeOfDayOrdersEmpty" class="chart-card__empty" @if($timeOfDayHasData) hidden @endif>Time-of-day data will appear once orders are recorded.</p>
                </div>
                <span class="insight-meta-caption" style="display:block; margin-top:0.4rem;">
                    @if($timeOfDayTop)
                        Peak ordering window: {{ $timeOfDayTop }}.
                    @else
                        Time-of-day breakdown unavailable.
                    @endif
                </span>
            </div>
        </article>
        <article class="dashboard-card">
            <header class="dashboard-card__header">
                <h2 class="dashboard-card__title">Day-of-week momentum</h2>
            </header>
            <div class="behavior-chart">
                <div class="insight-chart insight-chart--compact insight-chart--slim" style="margin:0;">
                    <span class="insight-meta-label">Day-of-week momentum</span>
                    <canvas id="dayOfWeekOrdersChart" data-chart='@json($dayOfWeekChart)'></canvas>
                    <p id="dayOfWeekOrdersEmpty" class="chart-card__empty" @if($dayOfWeekHasData) hidden @endif>Day-of-week trends will display once history is populated.</p>
                </div>
                <span class="insight-meta-caption" style="display:block; margin-top:0.4rem;">
                    @if($dayOfWeekTop && ($dayOfWeekTop['total'] ?? 0) > 0)
                        Busiest day: {{ $dayOfWeekTop['label'] }} ({{ number_format($dayOfWeekTop['total']) }} orders).
                    @else
                        No dominant day identified yet.
                    @endif
                </span>
            </div>
        </article>
        <article class="dashboard-card">
            <header class="dashboard-card__header">
                <h2 class="dashboard-card__title">Peak order dates</h2>
            </header>
            <div style="margin-top:0.85rem;">
                <div class="insight-chart insight-chart--wide insight-chart--slim" style="margin-bottom:0.65rem;">
                    <canvas id="peakOrderDaysMiniChart" data-chart='@json($peakOrderDaysChart)'></canvas>
                    <p id="peakOrderDaysMiniEmpty" class="chart-card__empty" @if($peakOrderDaysHasData) hidden @endif>Peak order patterns will plot once daily data is available.</p>
                </div>
                <ul class="analytics-card__list analytics-card__list--dense" style="margin:0;">
                    @forelse(collect($customerInsightsData['peakOrderDays']) as $day)
                        <li>{{ $day['day'] }} – {{ number_format($day['total_orders']) }} orders</li>
                    @empty
                        <li>Order timeline data not available yet.</li>
                    @endforelse
                </ul>
            </div>
        </article>
    </section>

            <header class="section-header">
            <div>
                <h2 class="section-title">Inventory</h2>
                <p class="section-subtitle">Stock levels, material tracking, and inventory health.</p>
            </div>
        </header>

    <section class="dashboard-grid" aria-label="Inventory">

        <article class="dashboard-card">
            <header class="dashboard-card__header">
                <h2 class="dashboard-card__title">Stock Status</h2>
                <span class="dashboard-card__meta">Current inventory levels and alerts</span>
            </header>
            <div class="stock-status-content">
                <div class="stock-chart-section">
                    <h4 style="margin:0 0 0.5rem 0; font-size:0.9rem; font-weight:600;">Top Materials</h4>
                    <div class="insight-chart insight-chart--wide">
                        <canvas id="materialStockChart" data-chart='@json($materialStockChart)'></canvas>
                        <p id="materialStockChartEmpty" class="chart-card__empty" @if($materialStockHasData) hidden @endif>No data</p>
                    </div>
                </div>
                <div class="stock-alerts-section">
                    <h4 style="margin:0 0 0.5rem 0; font-size:0.9rem; font-weight:600;">Alerts</h4>
                    <div class="stock-alerts-grid">
                        <div class="stock-alert">
                            <span style="font-size:0.8rem; color:var(--admin-text-secondary);">Low Stock</span>
                            <ul class="analytics-card__list analytics-card__list--dense" style="margin:0.25rem 0 0 0;">
                                @forelse(collect($inventoryMonitorData['lowStockMaterials'])->take(3) as $material)
                                    @php $stock = (int) (optional($material->inventory)->stock_level ?? $material->stock_qty ?? 0); @endphp
                                    <li style="font-size:0.8rem;">{{ Str::limit($material->material_name, 18) }} · {{ number_format($stock) }}</li>
                                @empty
                                    <li style="font-size:0.8rem;">All good</li>
                                @endforelse
                            </ul>
                        </div>
                        <div class="stock-alert">
                            <span style="font-size:0.8rem; color:var(--admin-text-secondary);">Out of Stock</span>
                            <ul class="analytics-card__list analytics-card__list--dense" style="margin:0.25rem 0 0 0;">
                                @forelse(collect($inventoryMonitorData['outOfStockMaterials'])->take(3) as $material)
                                    <li style="font-size:0.8rem;">{{ Str::limit($material->material_name, 18) }}</li>
                                @empty
                                    <li style="font-size:0.8rem;">None</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </article>

        <article class="dashboard-card">
            <header class="dashboard-card__header">
                <h2 class="dashboard-card__title">Recent Movements</h2>
                <span class="dashboard-card__meta">Stock changes and activity</span>
            </header>
            <div class="movement-log" style="max-height:150px;" aria-label="Recent stock movements">
                @forelse(collect($inventoryMonitorData['movementLogs'])->take(5) as $movement)
                    @php
                        $movementType = match($movement->movement_type) {
                            'restock' => 'In',
                            'usage' => 'Out',
                            'adjustment' => 'Adj',
                            default => Str::title($movement->movement_type ?? 'update')
                        };
                        $quantity = number_format(abs((int) $movement->quantity));
                        $unit = $movement->material?->unit ?? 'units';
                    @endphp
                    <div class="movement-log__entry" style="padding:0.5rem 0; border-bottom:1px solid var(--admin-border);">
                        <strong style="font-size:0.9rem;">{{ $movementType }} · {{ Str::limit($movement->material->material_name ?? 'Unknown', 25) }}</strong>
                        <div class="movement-log__meta" style="font-size:0.8rem;">{{ $quantity }} {{ $unit }} · {{ optional($movement->created_at)->diffForHumans() }}</div>
                    </div>
                @empty
                    <p class="analytics-card__empty" style="margin:1rem 0;">No recent movements</p>
                @endforelse
            </div>
            <div style="margin-top:0.5rem;">
                <a href="{{ route('admin.materials.index') }}" class="pill-link">Materials</a>
                <a href="{{ route('admin.inventory.index') }}" class="pill-link" style="margin-left:0.5rem;">History</a>
            </div>
        </article>

        <article class="dashboard-card">
            <header class="dashboard-card__header">
                <h2 class="dashboard-card__title">Inventory Demands</h2>
                <span class="dashboard-card__meta">Demand forecasting based on historical data</span>
            </header>
            <div class="demand-analysis-content">
                <div class="demand-chart-section">
                    <h4 style="margin:0 0 0.5rem 0; font-size:0.9rem; font-weight:600;">Demand Trends</h4>
                    <div class="insight-chart insight-chart--wide">
                        <canvas id="demandTrendsChart" data-chart='@json($demandTrendsChart ?? [])'></canvas>
                        <p id="demandTrendsChartEmpty" class="chart-card__empty" @if(!empty($demandTrendsChart) && count($demandTrendsChart['datasets'] ?? []) > 0) hidden @endif>No demand data available yet.</p>
                    </div>
                </div>
                <div class="demand-insights-section">
                    <h4 style="margin:0 0 0.5rem 0; font-size:0.9rem; font-weight:600;">Demand Insights</h4>
                    <div class="demand-insights-grid">
                        <div class="demand-insight-card">
                            <div class="demand-insight-header">
                                <span class="demand-insight-icon">📈</span>
                                <span class="demand-insight-label">Peak Demand</span>
                            </div>
                            <div class="demand-insight-value">
                                @if(!empty($demandInsights['peakPeriod']))
                                    {{ $demandInsights['peakPeriod']['period'] ?? 'N/A' }}
                                    <span class="demand-insight-subtext">{{ $demandInsights['peakPeriod']['usage'] ?? 0 }} units</span>
                                @else
                                    Analyzing...
                                @endif
                            </div>
                        </div>
                        <div class="demand-insight-card">
                            <div class="demand-insight-header">
                                <span class="demand-insight-icon">📉</span>
                                <span class="demand-insight-label">Low Demand</span>
                            </div>
                            <div class="demand-insight-value">
                                @if(!empty($demandInsights['lowPeriod']))
                                    {{ $demandInsights['lowPeriod']['period'] ?? 'N/A' }}
                                    <span class="demand-insight-subtext">{{ $demandInsights['lowPeriod']['usage'] ?? 0 }} units</span>
                                @else
                                    Analyzing...
                                @endif
                            </div>
                        </div>
                        <div class="demand-insight-card">
                            <div class="demand-insight-header">
                                <span class="demand-insight-icon">🔄</span>
                                <span class="demand-insight-label">Reorder Point</span>
                            </div>
                            <div class="demand-insight-value">
                                @if(!empty($demandInsights['recommendedReorder']))
                                    {{ number_format($demandInsights['recommendedReorder']) }} units
                                    <span class="demand-insight-subtext">Suggested level</span>
                                @else
                                    Calculating...
                                @endif
                            </div>
                        </div>
                        <div class="demand-insight-card">
                            <div class="demand-insight-header">
                                <span class="demand-insight-icon">📊</span>
                                <span class="demand-insight-label">Trend</span>
                            </div>
                            <div class="demand-insight-value">
                                @if(!empty($demandInsights['trend']))
                                    @if($demandInsights['trend'] === 'increasing')
                                        <span style="color: #28a745;">↗️ Increasing</span>
                                    @elseif($demandInsights['trend'] === 'decreasing')
                                        <span style="color: #dc3545;">↘️ Decreasing</span>
                                    @else
                                        <span style="color: #6c757d;">➡️ Stable</span>
                                    @endif
                                    <span class="demand-insight-subtext">{{ number_format(abs($demandInsights['trendPercent'] ?? 0), 1) }}% change</span>
                                @else
                                    Analyzing...
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div style="margin-top:0.5rem;">
                <a href="{{ route('admin.reports.inventory') }}" class="pill-link">View Details</a>
                <a href="{{ route('admin.inventory.index') }}" class="pill-link" style="margin-left:0.5rem;">Manage Stock</a>
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
            @php
                // Group pickups by date
                $groupedPickups = collect($upcomingCalendarData['upcomingOrders'] ?? [])->groupBy(function($pickup) {
                    $date = $pickup['date_needed'] ?? null;
                    return $date ? \Carbon\Carbon::parse($date)->format('Y-m-d') : 'unscheduled';
                })->sortKeys();

                // Get next 7 days for calendar view
                $calendarDays = [];
                $today = \Carbon\Carbon::today();
                for ($i = 0; $i < 7; $i++) {
                    $date = $today->copy()->addDays($i);
                    $dateKey = $date->format('Y-m-d');
                    $calendarDays[$dateKey] = [
                        'date' => $date,
                        'pickups' => $groupedPickups->get($dateKey, collect())
                    ];
                }
            @endphp
            <div class="pickup-calendar">
                @foreach($calendarDays as $dateKey => $dayData)
                    <div class="calendar-day {{ $dayData['date']->isToday() ? 'today' : '' }} {{ $dayData['pickups']->isNotEmpty() ? 'has-pickups' : '' }}">
                        <div class="calendar-day-header">
                            <span class="day-name">{{ $dayData['date']->format('D') }}</span>
                            <span class="day-number">{{ $dayData['date']->format('j') }}</span>
                        </div>
                        <div class="calendar-day-pickups">
                            @forelse($dayData['pickups'] as $pickup)
                                <div class="pickup-item">
                                    <div class="pickup-order">{{ $pickup['order_number'] }}</div>
                                    <div class="pickup-details">
                                        <span class="pickup-amount">₱{{ number_format($pickup['total_amount'] ?? 0, 2) }}</span>
                                        <span class="pickup-status {{ $pickup['status'] ?? 'pending' }}">{{ Str::title(str_replace('_', ' ', $pickup['status'] ?? 'pending')) }}</span>
                                    </div>
                                </div>
                            @empty
                                <div class="no-pickups">No pickups</div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
            @if($groupedPickups->has('unscheduled'))
                <div class="unscheduled-pickups">
                    <h4>Unscheduled Pickups</h4>
                    <ul class="analytics-card__list" style="margin:0;">
                        @foreach($groupedPickups['unscheduled'] as $pickup)
                            <li>
                                <strong>{{ $pickup['order_number'] }}</strong>
                                <span> · ₱{{ number_format($pickup['total_amount'] ?? 0, 2) }}</span>
                                <span> · {{ Str::title(str_replace('_', ' ', $pickup['status'] ?? 'pending')) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
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

        const readChartPayload = (canvas) => {
            if (!canvas) {
                return null;
            }

            try {
                return JSON.parse(canvas.dataset.chart || '{}');
            } catch (error) {
                return null;
            }
        };

        const destroyIfExists = (canvas) => {
            if (!canvas || !window.Chart || typeof window.Chart.getChart !== 'function') {
                return;
            }

            const existing = window.Chart.getChart(canvas);
            if (existing) {
                existing.destroy();
            }
        };

        const getPalette = (size) => {
            const base = ['#6A2EBC', '#3CD5C8', '#5A8DE0', '#F49D37', '#FF6B6B', '#4AD991', '#A664F0', '#3F8EFC'];
            if (size <= base.length) {
                return base.slice(0, size);
            }
            const repeats = Math.ceil(size / base.length);
            return Array.from({ length: repeats }, () => base).flat().slice(0, size);
        };

        const hasPositiveValues = (values) => Array.isArray(values) && values.some((value) => Number(value) > 0);

        const initialiseOrderStatusChart = () => {
            const canvas = document.getElementById('orderStatusChart');
            const emptyState = document.getElementById('orderStatusChartEmpty');
            if (!canvas) {
                return;
            }

            const payload = readChartPayload(canvas);
            const labels = Array.isArray(payload?.labels) ? payload.labels : [];
            const values = Array.isArray(payload?.values) ? payload.values.map((value) => Number(value) || 0) : [];
            const hasData = values.some((value) => value > 0);

            if (!hasData) {
                if (emptyState) {
                    emptyState.hidden = false;
                }
                return;
            }

            if (emptyState) {
                emptyState.hidden = true;
            }

            destroyIfExists(canvas);

            new window.Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels,
                    datasets: [
                        {
                            data: values,
                            backgroundColor: getPalette(values.length),
                            borderWidth: 0,
                        },
                    ],
                },
                options: {
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                    },
                    cutout: '62%',
                },
            });
        };

        const initialisePaymentStatusChart = () => {
            const canvas = document.getElementById('paymentStatusChart');
            const emptyState = document.getElementById('paymentStatusChartEmpty');
            if (!canvas) {
                return;
            }

            const payload = readChartPayload(canvas);
            const labels = Array.isArray(payload?.labels) ? payload.labels : [];
            const values = Array.isArray(payload?.values) ? payload.values.map((value) => Number(value) || 0) : [];
            const hasData = values.some((value) => value > 0);

            if (!hasData) {
                if (emptyState) {
                    emptyState.hidden = false;
                }
                return;
            }

            if (emptyState) {
                emptyState.hidden = true;
            }

            destroyIfExists(canvas);

            new window.Chart(canvas, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Payments',
                            data: values,
                            backgroundColor: getPalette(values.length),
                            borderRadius: 8,
                        },
                    ],
                },
                options: {
                    indexAxis: 'y',
                    plugins: {
                        legend: {
                            display: false,
                        },
                    },
                    scales: {
                        x: {
                            ticks: {
                                precision: 0,
                            },
                        },
                    },
                },
            });
        };

        const initialiseInventoryMixChart = () => {
            const canvas = document.getElementById('inventoryMixChart');
            const emptyState = document.getElementById('inventoryMixChartEmpty');
            if (!canvas) {
                return;
            }

            const payload = readChartPayload(canvas);
            const labels = Array.isArray(payload?.labels) ? payload.labels : [];
            const values = Array.isArray(payload?.values) ? payload.values.map((value) => Number(value) || 0) : [];
            const hasData = values.some((value) => value > 0);

            if (!hasData) {
                if (emptyState) {
                    emptyState.hidden = false;
                }
                return;
            }

            if (emptyState) {
                emptyState.hidden = true;
            }

            destroyIfExists(canvas);

            new window.Chart(canvas, {
                type: 'pie',
                data: {
                    labels,
                    datasets: [
                        {
                            data: values,
                            backgroundColor: ['#25A86B', '#F49D37', '#FF6B6B'],
                            borderWidth: 0,
                        },
                    ],
                },
                options: {
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                    },
                },
            });
        };

        const initialiseRepeatCustomersSplitChart = () => {
            const canvas = document.getElementById('repeatCustomersSplitChart');
            const emptyState = document.getElementById('repeatCustomersSplitEmpty');
            if (!canvas) {
                return;
            }

            const payload = readChartPayload(canvas);
            const labels = Array.isArray(payload?.labels) ? payload.labels : [];
            const values = Array.isArray(payload?.values) ? payload.values.map((value) => Number(value) || 0) : [];
            const hasData = values.some((value) => value > 0);

            if (!hasData) {
                if (emptyState) {
                    emptyState.hidden = false;
                }
                return;
            }

            if (emptyState) {
                emptyState.hidden = true;
            }

            destroyIfExists(canvas);

            new window.Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels,
                    datasets: [
                        {
                            data: values,
                            backgroundColor: ['#4AD991', '#6A2EBC'],
                            borderWidth: 0,
                        },
                    ],
                },
                options: {
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                    },
                    cutout: '58%',
                },
            });
        };

        const initialisePopularDesignsMiniChart = () => {
            const canvas = document.getElementById('popularDesignsMiniChart');
            const emptyState = document.getElementById('popularDesignsMiniEmpty');
            if (!canvas) {
                return;
            }

            const payload = readChartPayload(canvas);
            const labels = Array.isArray(payload?.labels) ? payload.labels : [];
            const values = Array.isArray(payload?.values) ? payload.values.map((value) => Number(value) || 0) : [];
            const hasData = values.some((value) => value > 0);

            if (!hasData) {
                if (emptyState) {
                    emptyState.hidden = false;
                }
                return;
            }

            if (emptyState) {
                emptyState.hidden = true;
            }

            destroyIfExists(canvas);

            new window.Chart(canvas, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Units sold',
                            data: values,
                            backgroundColor: getPalette(values.length),
                            borderRadius: 6,
                        },
                    ],
                },
                options: {
                    indexAxis: 'y',
                    plugins: {
                        legend: {
                            display: false,
                        },
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                            },
                        },
                    },
                },
            });
        };

        const initialiseTimeOfDayOrdersChart = () => {
            const canvas = document.getElementById('timeOfDayOrdersChart');
            const emptyState = document.getElementById('timeOfDayOrdersEmpty');
            if (!canvas) {
                return;
            }

            const payload = readChartPayload(canvas);
            const labels = Array.isArray(payload?.labels) ? payload.labels : [];
            const values = Array.isArray(payload?.values) ? payload.values.map((value) => Number(value) || 0) : [];
            const hasData = values.some((value) => value > 0);

            if (!hasData) {
                if (emptyState) {
                    emptyState.hidden = false;
                }
                return;
            }

            if (emptyState) {
                emptyState.hidden = true;
            }

            destroyIfExists(canvas);

            new window.Chart(canvas, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Orders',
                            data: values,
                            backgroundColor: ['#5C7CFA', '#4AD991', '#F49D37', '#FF6B6B'],
                            borderRadius: 6,
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
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                            },
                        },
                    },
                },
            });
        };

        const initialiseDayOfWeekOrdersChart = () => {
            const canvas = document.getElementById('dayOfWeekOrdersChart');
            const emptyState = document.getElementById('dayOfWeekOrdersEmpty');
            if (!canvas) {
                return;
            }

            const payload = readChartPayload(canvas);
            const labels = Array.isArray(payload?.labels) ? payload.labels : [];
            const values = Array.isArray(payload?.values) ? payload.values.map((value) => Number(value) || 0) : [];
            const hasData = values.some((value) => value > 0);

            if (!hasData) {
                if (emptyState) {
                    emptyState.hidden = false;
                }
                return;
            }

            if (emptyState) {
                emptyState.hidden = true;
            }

            destroyIfExists(canvas);

            new window.Chart(canvas, {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Orders',
                            data: values,
                            borderColor: '#6A2EBC',
                            backgroundColor: 'rgba(106, 46, 188, 0.16)',
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
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                            },
                        },
                    },
                },
            });
        };

        const initialisePeakOrderDaysMiniChart = () => {
            const canvas = document.getElementById('peakOrderDaysMiniChart');
            const emptyState = document.getElementById('peakOrderDaysMiniEmpty');
            if (!canvas) {
                return;
            }

            const payload = readChartPayload(canvas);
            const labels = Array.isArray(payload?.labels) ? payload.labels : [];
            const values = Array.isArray(payload?.values) ? payload.values.map((value) => Number(value) || 0) : [];
            const hasData = values.some((value) => value > 0);

            if (!hasData) {
                if (emptyState) {
                    emptyState.hidden = false;
                }
                return;
            }

            if (emptyState) {
                emptyState.hidden = true;
            }

            destroyIfExists(canvas);

            new window.Chart(canvas, {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Orders',
                            data: values,
                            borderColor: '#3CD5C8',
                            backgroundColor: 'rgba(60, 213, 200, 0.18)',
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
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                            },
                        },
                    },
                },
            });
        };

        const initialisePopularDesignsPrimaryChart = () => {
            const canvas = document.getElementById('popularDesignsPrimaryChart');
            const emptyState = document.getElementById('popularDesignsPrimaryEmpty');
            if (!canvas) {
                return;
            }

            const payload = readChartPayload(canvas);
            const labels = Array.isArray(payload?.labels) ? payload.labels : [];
            const values = Array.isArray(payload?.values) ? payload.values.map((value) => Number(value) || 0) : [];

            if (!hasPositiveValues(values)) {
                if (emptyState) {
                    emptyState.hidden = false;
                }
                return;
            }

            if (emptyState) {
                emptyState.hidden = true;
            }

            destroyIfExists(canvas);

            new window.Chart(canvas, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Units sold',
                            data: values,
                            backgroundColor: getPalette(values.length),
                            borderRadius: 8,
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
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                            },
                        },
                    },
                },
            });
        };

        const initialiseRatingDistributionChart = () => {
            const canvas = document.getElementById('ratingDistributionChart');
            const emptyState = document.getElementById('ratingDistributionChartEmpty');
            if (!canvas) {
                return;
            }

            const payload = readChartPayload(canvas);
            const labels = Array.isArray(payload?.labels) ? payload.labels : [];
            const values = Array.isArray(payload?.values) ? payload.values.map((value) => Number(value) || 0) : [];

            if (!hasPositiveValues(values)) {
                if (emptyState) {
                    emptyState.hidden = false;
                }
                return;
            }

            if (emptyState) {
                emptyState.hidden = true;
            }

            destroyIfExists(canvas);

            new window.Chart(canvas, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Reviews',
                            data: values,
                            backgroundColor: getPalette(values.length),
                            borderRadius: 6,
                        },
                    ],
                },
                options: {
                    indexAxis: 'y',
                    plugins: {
                        legend: {
                            display: false,
                        },
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                            },
                        },
                    },
                },
            });
        };

        const initialiseReviewResponseChart = () => {
            const canvas = document.getElementById('reviewResponseChart');
            const emptyState = document.getElementById('reviewResponseChartEmpty');
            if (!canvas) {
                return;
            }

            const payload = readChartPayload(canvas);
            const labels = Array.isArray(payload?.labels) ? payload.labels : [];
            const values = Array.isArray(payload?.values) ? payload.values.map((value) => Number(value) || 0) : [];

            if (!hasPositiveValues(values)) {
                if (emptyState) {
                    emptyState.hidden = false;
                }
                return;
            }

            if (emptyState) {
                emptyState.hidden = true;
            }

            destroyIfExists(canvas);

            new window.Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels,
                    datasets: [
                        {
                            data: values,
                            backgroundColor: ['#25A86B', '#F49D37'],
                            borderWidth: 0,
                        },
                    ],
                },
                options: {
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                    },
                    cutout: '60%',
                },
            });
        };

        const initialiseStockLevelsChart = () => {
            const canvas = document.getElementById('stockLevelsChartCanvas');
            const emptyState = document.getElementById('stockLevelsChartEmpty');
            if (!canvas) {
                return;
            }

            const payload = readChartPayload(canvas);
            const labels = Array.isArray(payload?.labels) ? payload.labels : [];
            const values = Array.isArray(payload?.values) ? payload.values.map((value) => Number(value) || 0) : [];

            if (!hasPositiveValues(values)) {
                if (emptyState) {
                    emptyState.hidden = false;
                }
                return;
            }

            if (emptyState) {
                emptyState.hidden = true;
            }

            destroyIfExists(canvas);

            new window.Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels,
                    datasets: [
                        {
                            data: values,
                            backgroundColor: ['#4AD991', '#F49D37', '#FF6B6B'],
                            borderWidth: 0,
                        },
                    ],
                },
                options: {
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                    },
                    cutout: '58%',
                },
            });
        };

        const initialiseCriticalMaterialsChart = () => {
            const canvas = document.getElementById('criticalMaterialsChart');
            const emptyState = document.getElementById('criticalMaterialsChartEmpty');
            if (!canvas) {
                return;
            }

            const payload = readChartPayload(canvas);
            const labels = Array.isArray(payload?.labels) ? payload.labels : [];
            const values = Array.isArray(payload?.values) ? payload.values.map((value) => Number(value) || 0) : [];

            if (!hasPositiveValues(values)) {
                if (emptyState) {
                    emptyState.hidden = false;
                }
                return;
            }

            if (emptyState) {
                emptyState.hidden = true;
            }

            destroyIfExists(canvas);

            new window.Chart(canvas, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Units to restock',
                            data: values,
                            backgroundColor: getPalette(values.length),
                            borderRadius: 6,
                        },
                    ],
                },
                options: {
                    indexAxis: 'y',
                    plugins: {
                        legend: {
                            display: false,
                        },
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                            },
                        },
                    },
                },
            });
        };

        const initialiseMaterialStockChart = () => {
            const canvas = document.getElementById('materialStockChart');
            const emptyState = document.getElementById('materialStockChartEmpty');
            if (!canvas) {
                return;
            }

            const payload = readChartPayload(canvas);
            const labels = Array.isArray(payload?.labels) ? payload.labels : [];
            const values = Array.isArray(payload?.values) ? payload.values.map((value) => Number(value) || 0) : [];
            const colors = Array.isArray(payload?.colors) ? payload.colors : [];
            const units = Array.isArray(payload?.units) ? payload.units : [];

            if (!hasPositiveValues(values)) {
                canvas.hidden = true;
                if (emptyState) {
                    emptyState.hidden = false;
                }
                return;
            }

            canvas.hidden = false;
            if (emptyState) {
                emptyState.hidden = true;
            }

            destroyIfExists(canvas);

            new window.Chart(canvas, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [
                        {
                            data: values,
                            backgroundColor: colors.length ? colors : getPalette(values.length),
                            borderRadius: 12,
                            borderSkipped: false,
                            maxBarThickness: 28,
                        },
                    ],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false,
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    const rawValue = Number(context.parsed.x ?? context.raw ?? 0);
                                    const unit = units[context.dataIndex] ?? 'units';
                                    return `${rawValue.toLocaleString()} ${unit}`;
                                },
                            },
                        },
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(148, 185, 255, 0.12)',
                            },
                            ticks: {
                                precision: 0,
                                callback: (tickValue) => Number(tickValue).toLocaleString(),
                            },
                        },
                        y: {
                            grid: {
                                display: false,
                            },
                            ticks: {
                                autoSkip: false,
                                maxRotation: 0,
                                minRotation: 0,
                            },
                        },
                    },
                },
            });
        };

        const initialiseTopCustomersChart = () => {
            const canvas = document.getElementById('topCustomersChart');
            const emptyState = document.getElementById('topCustomersChartEmpty');
            if (!canvas) {
                return;
            }

            const payload = readChartPayload(canvas);
            const labels = Array.isArray(payload?.labels) ? payload.labels : [];
            const spendValues = Array.isArray(payload?.values) ? payload.values.map((value) => Number(value) || 0) : [];
            const orderValues = Array.isArray(payload?.orders) ? payload.orders.map((value) => Number(value) || 0) : [];
            const hasSpend = spendValues.some((value) => value > 0);
            const hasOrders = orderValues.some((value) => value > 0);

            if (!hasSpend && !hasOrders) {
                if (emptyState) {
                    emptyState.hidden = false;
                }
                return;
            }

            if (emptyState) {
                emptyState.hidden = true;
            }

            destroyIfExists(canvas);

            const datasets = [
                {
                    type: 'bar',
                    label: 'Total spend',
                    data: spendValues,
                    backgroundColor: getPalette(spendValues.length),
                    borderRadius: 8,
                    order: 1,
                },
            ];

            if (hasOrders) {
                datasets.push({
                    type: 'line',
                    label: 'Orders',
                    data: orderValues,
                    borderColor: '#3CD5C8',
                    backgroundColor: 'rgba(60, 213, 200, 0.15)',
                    tension: 0.3,
                    fill: false,
                    yAxisID: 'yOrders',
                    order: 0,
                });
            }

            const chartOptions = {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback(value) {
                                return '₱' + Number(value).toLocaleString();
                            },
                        },
                    },
                    yOrders: {
                        beginAtZero: true,
                        position: 'right',
                        grid: {
                            display: false,
                        },
                        ticks: {
                            precision: 0,
                        },
                    },
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                },
            };

            if (!hasOrders && chartOptions.scales) {
                delete chartOptions.scales.yOrders;
            }

            new window.Chart(canvas, {
                type: 'bar',
                data: {
                    labels,
                    datasets,
                },
                options: chartOptions,
            });
        };

        const initialiseDemandTrendsChart = () => {
            const canvas = document.getElementById('demandTrendsChart');
            const emptyState = document.getElementById('demandTrendsChartEmpty');
            if (!canvas) {
                return;
            }

            const payload = readChartPayload(canvas);
            const labels = Array.isArray(payload?.labels) ? payload.labels : [];
            const datasets = Array.isArray(payload?.datasets) ? payload.datasets : [];

            if (!datasets.length || !labels.length) {
                canvas.hidden = true;
                if (emptyState) {
                    emptyState.hidden = false;
                }
                return;
            }

            canvas.hidden = false;
            if (emptyState) {
                emptyState.hidden = true;
            }

            destroyIfExists(canvas);

            new window.Chart(canvas, {
                type: 'line',
                data: {
                    labels,
                    datasets: datasets.map(dataset => ({
                        ...dataset,
                        borderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                    })),
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    const label = context.dataset.label || '';
                                    const value = Number(context.parsed.y ?? context.raw ?? 0);
                                    return `${label}: ${value.toLocaleString()} units`;
                                },
                            },
                        },
                    },
                    scales: {
                        x: {
                            grid: {
                                color: 'rgba(148, 185, 255, 0.12)',
                            },
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(148, 185, 255, 0.12)',
                            },
                            ticks: {
                                precision: 0,
                                callback: (tickValue) => Number(tickValue).toLocaleString(),
                            },
                        },
                    },
                },
            });
        };

        const initialiseDashboardCharts = () => {
            if (typeof window.Chart === 'undefined') {
                setTimeout(initialiseDashboardCharts, 250);
                return;
            }

            initialiseOrderStatusChart();
            initialisePaymentStatusChart();
            initialiseInventoryMixChart();
            initialiseTopCustomersChart();
            initialiseRepeatCustomersSplitChart();
            initialisePopularDesignsMiniChart();
            initialiseTimeOfDayOrdersChart();
            initialiseDayOfWeekOrdersChart();
            initialisePeakOrderDaysMiniChart();
            initialisePopularDesignsPrimaryChart();
            initialiseRatingDistributionChart();
            initialiseReviewResponseChart();
            initialiseStockLevelsChart();
            initialiseCriticalMaterialsChart();
            initialiseMaterialStockChart();
            initialiseDemandTrendsChart();
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initialiseSalesTrendChart);
            document.addEventListener('DOMContentLoaded', initialiseDashboardCharts);
        } else {
            initialiseSalesTrendChart();
            initialiseDashboardCharts();
        }
    </script>
</main>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js" defer></script>
@endpush
