@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="df-panel p-4">
                <h2 class="h4 mb-4">Profile Settings</h2>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Name</label>
                            <input type="text" class="form-control" value="Abdul Aziz" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" class="form-control" value="abdulaziz@dragonfortune.ai" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Role</label>
                            <input type="text" class="form-control" value="Admin" readonly>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Avatar</label>
                            <div class="d-flex align-items-center gap-3">
                                <img src="/images/avatar.svg" alt="Avatar" class="rounded-circle" style="width: 80px; height: 80px; border: 2px solid var(--border);">
                                <button class="btn btn-sm btn-outline-secondary" disabled>Change Avatar</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info mt-4">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 16v-4"/>
                        <path d="M12 8h.01"/>
                    </svg>
                    Halaman profile sedang dalam pengembangan. Fitur lengkap akan segera tersedia.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

