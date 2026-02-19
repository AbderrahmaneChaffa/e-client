<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('page-title') - E-Client</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5568d3;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --dark: #1f2937;
            --light: #f3f4f6;
        }

        body {
            background-color: var(--light);
            color: var(--dark);
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 250px;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .sidebar-link:hover,
        .sidebar-link.active {
            color: var(--primary);
            background-color: rgba(102, 126, 234, 0.1);
            border-left-color: var(--primary);
        }

        .sidebar-link i {
            width: 20px;
            text-align: center;
        }

        .content-wrapper {
            margin-left: 250px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .topbar {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-bottom: 4px solid #5568d3;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .topbar-title {
            font-size: 24px;
            font-weight: bold;
            color: white;
        }

        .main-content {
            flex: 1;
            padding: 24px;
        }

        .card {
            background: white;
            border-radius: 8px;
            padding: 24px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .kpi-card {
            background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
        }

        .badge-paid {
            background-color: #d1fae5;
            color: var(--success);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-pending {
            background-color: #fef3c7;
            color: var(--warning);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-unpaid {
            background-color: #fee2e2;
            color: var(--danger);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            padding: 10px 16px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-weight: 500;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: white;
            color: var(--primary);
            padding: 10px 16px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            border: 1px solid var(--primary);
            cursor: pointer;
            font-weight: 500;
        }

        .btn-secondary:hover {
            background: var(--primary);
            color: white;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }

        .form-input,
        .form-select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table thead {
            background-color: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
        }

        .table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: var(--dark);
        }

        .table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        .table tbody tr:hover {
            background-color: #f9fafb;
        }

        .alert {
            padding: 16px;
            border-radius: 6px;
            margin-bottom: 16px;
        }

        .alert-success {
            background-color: #d1fae5;
            color: var(--success);
            border: 1px solid #a7f3d0;
        }

        .alert-danger {
            background-color: #fee2e2;
            color: var(--danger);
            border: 1px solid #fecaca;
        }

        .alert-warning {
            background-color: #fef3c7;
            color: var(--warning);
            border: 1px solid #fcd34d;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal.active {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            padding: 24px;
            border-radius: 8px;
            max-width: 600px;
            width: 90%;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                overflow-x: hidden;
            }

            .content-wrapper {
                margin-left: 0;
            }

            .sidebar.active {
                width: 250px;
            }
        }
    </style>
    @yield('extra-css')
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
</head>

<body>
    <div class="sidebar" id="sidebar">
        <div style="padding: 24px; border-bottom: 1px solid #334155;">
            <div style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 8px;">
                <img src="{{ asset('storage/Logo/petit taille.png') }}" alt="Logo" style="height: 32px; width: auto;">
                <h1 style="color: white; font-size: 18px; font-weight: bold; margin: 0;">E-Client</h1>
            </div>
            <p style="color: #94a3b8; font-size: 12px; margin: 4px 0 0 0; text-align: center;">Portail Client</p>
        </div>

        <nav style="padding-top: 16px;">
            <a href="{{ route('client.dashboard') }}" class="sidebar-link {{ request()->routeIs('client.dashboard') ? 'active' : '' }}">
                <i class="fas fa-chart-line"></i>
                <span>Tableau de bord</span>
            </a>
            <a href="{{ route('client.factures.index') }}" class="sidebar-link {{ request()->routeIs('client.factures*') ? 'active' : '' }}">
                <i class="fas fa-file-invoice"></i>
                <span>Factures</span>
            </a>
            <a href="{{ route('client.paiements.index') }}" class="sidebar-link {{ request()->routeIs('client.paiements*') ? 'active' : '' }}">
                <i class="fas fa-credit-card"></i>
                <span>Paiements</span>
            </a>
            <a href="#" class="sidebar-link {{ request()->routeIs('client.documents*') ? 'active' : '' }}">
                <i class="fas fa-folder"></i>
                <span>Documents</span>
            </a>
            <a href="#" class="sidebar-link {{ request()->routeIs('client.profil') ? 'active' : '' }}">
                <i class="fas fa-user"></i>
                <span>Mon Profil</span>
            </a>
            <a href="#" class="sidebar-link {{ request()->routeIs('client.parametres') ? 'active' : '' }}">
                <i class="fas fa-cog"></i>
                <span>Paramètres</span>
            </a>
        </nav>

        <div style="position: absolute; bottom: 0; width: 100%; border-top: 1px solid #334155; padding: 16px 20px;">
            <a href="{{ route('logout') }}" class="sidebar-link" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="fas fa-sign-out-alt"></i>
                <span>Déconnexion</span>
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
        </div>
    </div>

    <div class="content-wrapper">
        <div class="topbar">
            <div class="topbar-title">@yield('page-title')</div>
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="text-align: right;">
                    <p style="margin: 0; font-weight: 600; color: white;">{{ Auth::user()->name ?? 'Client' }}</p>
                    <p style="margin: 4px 0 0 0; font-size: 12px; color: #e0e7ff;">{{ Auth::user()->email ?? '' }}</p>
                </div>
                <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name ?? 'Client') }}&background=667eea&color=fff" alt="Avatar" style="width: 40px; height: 40px; border-radius: 50%; border: 2px solid white;">
            </div>
        </div>

        <div class="main-content">
            @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Erreurs:</strong>
                <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            @if (session('success'))
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                {{ session('success') }}
            </div>
            @endif

            @if (session('error'))
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                {{ session('error') }}
            </div>
            @endif

            @yield('content')
        </div>
    </div>

    <script>
        // Mobile sidebar toggle
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            window.toggleSidebar = function() {
                sidebar.classList.toggle('active');
            };
        });
    </script>
    <!-- jQuery + DataTables -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof jQuery !== 'undefined' && typeof jQuery.fn.DataTable !== 'undefined') {
                jQuery('table.table').each(function() {
                    if (!jQuery.fn.DataTable.isDataTable(this)) {
                        jQuery(this).DataTable({
                            responsive: true,
                            pageLength: 20,
                            lengthMenu: [5, 10, 25, 50, 100],
                            order: [],
                            language: {
                                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json'
                            }
                        });
                    }
                });
            }
        });
    </script>
    @yield('extra-js')
</body>

</html>