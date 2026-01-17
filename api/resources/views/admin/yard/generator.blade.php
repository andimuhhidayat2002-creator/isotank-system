@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Yard Layout Generator</h2>
        <div>
            <a href="{{ route('admin.yard.generator.csv') }}" class="btn btn-success">
                <i class="bi bi-download"></i> Download CSV Configuration
            </a>
            <a href="{{ route('yard.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Live Yard
            </a>
        </div>
    </div>

    <!-- Upload Excel Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-excel"></i> Update Layout from Excel</h5>
                </div>
                <div class="card-body">
                    <p>Upload a visual Excel layout (cells marked with 'x') to automatically update the active slots below.</p>
                    <form action="{{ route('admin.yard.generator.import') }}" method="POST" enctype="multipart/form-data" class="d-flex gap-2 align-items-center">
                        @csrf
                        <input type="file" name="file" class="form-control w-auto" accept=".xlsx, .xls" required>
                        <button type="submit" class="btn btn-primary">Parse & Update Map</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="alert alert-info">
                The Excel-to-CSV Converter is ready. Upload your Excel layout above, then download the generated CSV.
            </div>
        </div>
    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
