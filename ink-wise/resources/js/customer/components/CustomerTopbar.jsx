import React, { useEffect, useMemo, useState } from 'react';

const noop = () => {};

const defaultNavLinks = [
    { label: 'Showcase', href: '#dashboard' },
    { label: 'Collections', href: '#categories' },
    { label: 'Templates', href: '#templates' },
    { label: 'About', href: '#about' },
    { label: 'Contact', href: '#contact' },
];

const clampLinks = (links = []) => {
    if (!Array.isArray(links)) {
        return [];
    }
    return links.filter(Boolean).slice(0, 7);
};

const IconButton = ({ label, badge, onClick, iconClass, href, disabled }) => {
    const content = (
        <button
            type="button"
            className="customer-icon-button"
            aria-label={label}
            onClick={disabled ? noop : onClick}
            disabled={disabled}
        >
            <i className={iconClass} aria-hidden="true"></i>
            {badge ? <span className="notification-badge">{badge}</span> : null}
        </button>
    );

    if (href && !onClick) {
        return (
            <a className="customer-icon-button" aria-label={label} href={href}>
                <i className={iconClass} aria-hidden="true"></i>
                {badge ? <span className="notification-badge">{badge}</span> : null}
            </a>
        );
    }

    return content;
};

const buildLinkKey = (label, href) => `${label}-${href}`;

const defaultAuth = {
    isAuthenticated: false,
    name: 'Guest',
    initials: 'IN',
    profileUrl: '/customer/profile',
    purchaseUrl: '/customer/my-purchase',
    favoritesUrl: '/customer/favorites',
};

const CustomerTopbar = (props = {}) => {
    const {
        navLinks: incomingNavLinks = [],
        searchEndpoint = '#',
        notificationsUrl = '#',
        favoritesUrl = '#',
        cartUrl = '/order/addtocart',
        orderSummaryEndpoint = '/order/summary.json',
        cartItemsEndpoint = '/order/cart/items',
        storageKey = 'inkwise-finalstep',
        csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
        loginUrl = '/customer/login',
        auth: incomingAuth = defaultAuth,
        unreadCount = 0,
    } = props;

    const auth = { ...defaultAuth, ...(incomingAuth || {}) };
    const navLinks = clampLinks(incomingNavLinks.length ? incomingNavLinks : defaultNavLinks);

    const [navOpen, setNavOpen] = useState(false);
    const [searchOpen, setSearchOpen] = useState(false);
    const [dropdownOpen, setDropdownOpen] = useState(false);
    const [accentHue, setAccentHue] = useState(() => Math.floor(Math.random() * 360));

    useEffect(() => {
        const interval = window.setInterval(() => {
            setAccentHue((prev) => (prev + 17) % 360);
        }, 4000);
        return () => window.clearInterval(interval);
    }, []);

    useEffect(() => {
        document.body.classList.toggle('customer-nav-open', navOpen);
        return () => document.body.classList.remove('customer-nav-open');
    }, [navOpen]);

    useEffect(() => {
        const handler = (event) => {
            if (!event.target.closest('.customer-topbar')) {
                setDropdownOpen(false);
                setSearchOpen(false);
            }
        };
        document.addEventListener('click', handler);
        return () => document.removeEventListener('click', handler);
    }, []);

    const serverHasOrder = async () => {
        try {
            const response = await fetch(orderSummaryEndpoint, {
                method: 'GET',
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            return response.ok;
        } catch (error) {
            console.warn('[InkWise] Unable to reach order summary', error);
            return false;
        }
    };

    const createOrderFromSummary = async (summary) => {
        if (!summary) return false;
        const productId = summary.productId ?? summary.product_id;
        const quantity = Number(summary.quantity ?? 10);
        if (!productId) return false;
        try {
            const response = await fetch(cartItemsEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                },
                credentials: 'same-origin',
                body: JSON.stringify({ product_id: Number(productId), quantity }),
            });
            return response.ok;
        } catch (error) {
            console.warn('[InkWise] Unable to create order from summary', error);
            return false;
        }
    };

    const focusSearchInput = () => {
        window.requestAnimationFrame(() => {
            const input = document.querySelector('.customer-topbar__search-panel input[type="text"]');
            input?.focus();
        });
    };

    const handleCartClick = async (event) => {
        event.preventDefault();
        try {
            if (await serverHasOrder()) {
                window.location.href = cartUrl;
                return;
            }
            let summary = null;
            try {
                const raw = window.sessionStorage.getItem(storageKey);
                summary = raw ? JSON.parse(raw) : null;
            } catch (error) {
                summary = null;
            }
            if (summary && (summary.productId || summary.product_id)) {
                const created = await createOrderFromSummary(summary);
                if (created) {
                    window.location.href = cartUrl;
                    return;
                }
            }
            window.location.href = cartUrl;
        } catch (error) {
            console.warn('[InkWise] Cart shortcut failed', error);
            window.location.href = '/order/summary';
        }
    };

    const handleFavoritesClick = (event) => {
        event.preventDefault();
        window.location.href = favoritesUrl;
    };

    const handleNotificationsClick = (event) => {
        event.preventDefault();
        window.location.href = notificationsUrl;
    };

    const handleLogout = () => {
        const form = document.getElementById('customerLogoutForm');
        if (form) {
            form.submit();
        }
    };

    const callToAction = auth.isAuthenticated ? (
        <button
            className="customer-pill"
            onClick={() => setDropdownOpen((open) => !open)}
            aria-expanded={dropdownOpen}
        >
            <span className="customer-avatar" aria-hidden="true">
                <span>{auth.initials}</span>
            </span>
            <span className="customer-pill__label">{auth.name}</span>
            <i className="fi fi-br-angle-small-down" aria-hidden="true"></i>
        </button>
    ) : (
        <a className="customer-pill" href={loginUrl}>
            <span className="customer-pill__label">Sign in</span>
            <i className="fi fi-br-enter" aria-hidden="true"></i>
        </a>
    );

    const dropdown = auth.isAuthenticated ? (
        <div className={`customer-dropdown ${dropdownOpen ? 'is-open' : ''}`} role="menu">
            <a href={auth.profileUrl} role="menuitem">My Account</a>
            <a href={auth.purchaseUrl} role="menuitem">My Purchase</a>
            <a href={auth.favoritesUrl} role="menuitem">My Favorites</a>
            <button type="button" role="menuitem" onClick={handleLogout}>Logout</button>
        </div>
    ) : null;

    const accentStyle = { '--customer-accent-hue': accentHue };

    return (
        <header className={`customer-topbar ${navOpen ? 'is-open' : ''}`} style={accentStyle}>
            <div className="customer-topbar__glow" aria-hidden="true"></div>
            <div className="customer-topbar__trail" aria-hidden="true"></div>
            <div className="customer-topbar__inner">
                <div className="customer-topbar__brand">
                    <span className="brandmark__initial">I</span>
                    <div className="brandmark__wordmark">
                        <span className="brandmark__word">nkwise</span>
                        <span className="brandmark__tagline">Elegant invites & thoughtful giveaways</span>
                    </div>
                </div>

                <nav className="customer-topbar__nav" aria-label="Primary">
                    {navLinks.map(({ label, href }) => (
                        <a key={buildLinkKey(label, href)} href={href} onClick={() => setNavOpen(false)}>
                            <span>{label}</span>
                        </a>
                    ))}
                </nav>

                <div className="customer-topbar__actions">
                    <button
                        className="customer-icon-button customer-icon-button--ghost"
                        aria-label="Search"
                        onClick={() => {
                            setSearchOpen((open) => !open);
                            setTimeout(focusSearchInput, 150);
                        }}
                    >
                        <i className="fi fi-br-search" aria-hidden="true"></i>
                    </button>
                    <IconButton
                        label="Notifications"
                        badge={unreadCount > 0 ? unreadCount : null}
                        iconClass="fi fi-br-bell"
                        onClick={handleNotificationsClick}
                    />
                    <IconButton
                        label="Favorites"
                        iconClass="fi fi-br-comment-heart"
                        onClick={handleFavoritesClick}
                    />
                    <IconButton
                        label="Cart"
                        iconClass="bi bi-bag-heart-fill"
                        onClick={handleCartClick}
                    />
                    {callToAction}
                    <button
                        className="customer-topbar__toggle"
                        aria-label="Toggle navigation"
                        aria-expanded={navOpen}
                        onClick={() => setNavOpen((open) => !open)}
                    >
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>

                {dropdown}
            </div>

            <div className={`customer-topbar__search-panel ${searchOpen ? 'is-open' : ''}`}>
                <form action={searchEndpoint} method="GET">
                    <label htmlFor="customerTopbarSearch" className="sr-only">Search templates</label>
                    <input
                        id="customerTopbarSearch"
                        type="text"
                        name="query"
                        placeholder="Search dreamy templates, giveaways, names..."
                    />
                    <button type="submit">Search</button>
                    <button type="button" className="customer-topbar__close" onClick={() => setSearchOpen(false)}>
                        <i className="fi fi-br-cross-small" aria-hidden="true"></i>
                    </button>
                </form>
            </div>
        </header>
    );
};

export default CustomerTopbar;
