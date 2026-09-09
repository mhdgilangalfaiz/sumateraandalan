<style>
    /* ===== NAVBAR (shared — includes/navbar-style.php) ===== */
    .navbar {
        background: transparent;
        padding: 1.2rem 0;
        transition: background .35s ease, padding .35s ease, box-shadow .35s ease;
        position: fixed;
        width: 100%;
        top: 0;
        z-index: 1000;
    }

    .navbar.scrolled {
        background: rgba(27, 77, 46, .97);
        backdrop-filter: blur(10px);
        padding: .8rem 0;
        box-shadow: 0 4px 30px rgba(0, 0, 0, .2);
    }

    /* Khusus halaman yang pakai $navHideUntilScroll = true (beranda):
       navbar tersembunyi total di paling atas, baru muncul saat discroll */
    .navbar.nav-hero {
        opacity: 0;
        transform: translateY(-100%);
        pointer-events: none;
    }

    .navbar.nav-hero.scrolled {
        opacity: 1;
        transform: translateY(0);
        pointer-events: auto;
    }

    .navbar-brand {
        font-family: var(--font-display);
        font-size: 1.4rem;
        font-weight: 700;
        color: #fff !important;
        letter-spacing: .5px;
        display: flex;
        align-items: center;
    }

    .navbar-brand span {
        color: var(--emas);
    }

    .navbar-logo {
        height: 58px;
        width: auto;
        display: block;
    }

    @media (max-width: 991px) {
        .navbar-logo {
            height: 50px;
        }
    }

    @media (max-width: 480px) {
        .navbar-logo {
            height: 42px;
        }
    }

    .navbar-toggler {
        padding: .25rem .5rem;
    }

    .navbar-toggler:focus {
        box-shadow: none;
    }

    .nav-link {
        color: rgba(255, 255, 255, .9) !important;
        font-weight: 500;
        font-size: .9rem;
        letter-spacing: .3px;
        padding: .5rem 1rem !important;
        transition: color .3s;
    }

    .nav-link:hover,
    .nav-link.active {
        color: var(--emas) !important;
    }

    .btn-navbar {
        background: var(--emas);
        color: var(--hijau-tua) !important;
        border-radius: 50px;
        padding: .5rem 1.4rem !important;
        font-weight: 600;
        font-size: .85rem;
        transition: all .3s;
    }

    .btn-navbar:hover {
        background: var(--emas-muda);
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(201, 168, 76, .4);
    }

    .dropdown-menu {
        border: none;
        border-radius: 10px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, .12);
        padding: .5rem;
        margin-top: .5rem !important;
    }

    .dropdown-item {
        border-radius: 8px;
        font-size: .9rem;
        padding: .55rem .8rem;
        color: var(--teks-gelap);
    }

    .dropdown-item:hover {
        background: var(--krem);
    }

    .dropdown-item.active {
        background: var(--krem-tua, var(--krem));
        color: var(--hijau-tua) !important;
        font-weight: 600;
    }

    @media (max-width: 991px) {
        .navbar-collapse {
            background: rgba(27, 77, 46, .98);
            border-radius: 14px;
            padding: 14px 18px;
            margin-top: 12px;
        }

        .dropdown-menu {
            background: rgba(255, 255, 255, .06);
            box-shadow: none;
        }

        .dropdown-item {
            color: #fff;
        }

        .dropdown-item:hover {
            background: rgba(255, 255, 255, .1);
        }
    }
</style>