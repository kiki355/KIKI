<nav aria-label="breadcrumb">
    <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-white" href="javascript:;">Pages</a></li>
        <li class="breadcrumb-item text-sm text-white active" aria-current="page">{{ $page }}</li>
        @if ($active != "")
            <li class="breadcrumb-item text-sm text-white active" aria-current="page">{{ $active }}</li>
        @endif
    </ol>
    <h6 class="font-weight-bolder text-white mb-0">{{ $title }}</h6>
</nav>
