<x-guest-layout>
    <div class="card-body login-card-body">
        <p class="login-box-msg">Forgot your password? No problem. Just let us know your email address and we will email you a password reset link.</p>

        <x-auth-session-status class="mb-4" :status="session('status')" />
        
        <x-auth-validation-errors class="mb-4" :errors="$errors" />

        <form action="{{ route('password.email') }}" method="POST">
            @csrf
            
            <div class="input-group mb-3">
                <input type="email" name="email" class="form-control" placeholder="Email" value="{{ old('email') }}" required autofocus>
                <div class="input-group-append">
                    <div class="input-group-text">
                        <span class="fas fa-envelope"></span>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-block">Email Password Reset Link</button>
                </div>
                </div>
        </form>

        <p class="mt-3 mb-1">
            <a href="{{ route('login') }}">Login</a>
        </p>
    </div>
    </x-guest-layout>