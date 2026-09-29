{{-- Scripts de la plantilla Phoenix. No usar polyfill.io: el dominio está comprometido desde 2024. --}}
<script src="{{ asset('vendors/popper/popper.min.js') }}"></script>
<script src="{{ asset('vendors/bootstrap/bootstrap.min.js') }}"></script>
<script src="{{ asset('vendors/anchorjs/anchor.min.js') }}"></script>
<script src="{{ asset('vendors/is/is.min.js') }}"></script>
<script src="{{ asset('vendors/fontawesome/all.min.js') }}"></script>
<script src="{{ asset('vendors/lodash/lodash.min.js') }}"></script>
<script src="{{ asset('vendors/list.js/list.min.js') }}"></script>
<script src="{{ asset('vendors/feather-icons/feather.min.js') }}"></script>
<script src="{{ asset('vendors/dayjs/dayjs.min.js') }}"></script>
@stack('vendors')
<script src="{{ asset('assets/js/phoenix.js') }}"></script>
@stack('scripts')
