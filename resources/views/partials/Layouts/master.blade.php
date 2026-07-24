<!DOCTYPE html>
<html lang="en">

<meta charset="utf-8" />
<title>@yield('title', 'Saudi HR & Employee Management')</title>
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<meta content="Admin & Dashboards Template" name="description" />
<meta content="Pixeleyez" name="author" />

<!-- layout setup -->
<script type="module" src="assets/js/layout-setup.js"></script>

<!-- App favicon -->
<link rel="shortcut icon" href="assets/images/k_favicon_32x.png">

<style>
    [x-cloak] { display: none !important; }
    /* Alpine's x-show removes the inline display override when showing an
       element instead of restoring it, so these hand-rolled Bootstrap modals
       (toggled via x-show, not Bootstrap's own Modal JS) need their own
       higher-specificity rule to fall back to "block" instead of Bootstrap's
       base ".modal { display: none }". Alpine's explicit inline display:none
       on hide still wins via inline-style specificity. */
    .modal.fade.show { display: block; }
</style>
@yield('css')
@include('partials.head-css')

<body x-data="spaApp" x-cloak>

    @include('partials.header')
    @include('partials.sidebar')
    @include('partials.horizontal')

    <main class="app-wrapper">
        <div class="container-fluid">

            @include('partials.page-title')

            @yield('content')
        </div>
        <!-- End container-fluid -->

        @include('partials.footer')
    </main>
    <!-- End main -->

    @include('partials.switcher')
    @include('partials.scroll-to-top')
    
    @include('partials.vendor-scripts')

    @yield('js')

</body>

</html>
