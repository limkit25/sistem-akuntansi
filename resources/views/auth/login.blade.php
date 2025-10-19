<x-guest-layout>
    <div class="card-body login-card-body">
        <p class="login-box-msg">Sign in to start your session</p>

        <x-auth-session-status class="mb-4" :status="session('status')" />
        
        <x-auth-validation-errors class="mb-4" :errors="$errors" />

        <form action="{{ route('login') }}" method="POST">
            @csrf
            
            <div class="input-group mb-3">
                <input type="email" name="email" class="form-control" placeholder="Email" value="{{ old('email') }}" required autofocus>
                <div class="input-group-append">
                    <div class="input-group-text">
                        <span class="fas fa-envelope"></span>
                    </div>
                </div>
            </div>
            
            <div class="input-group mb-3">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
                <div class="input-group-append">
                    <div class="input-group-text">
                        <span class="fas fa-lock"></span>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-8">
                    <div class="icheck-primary">
                        <input type="checkbox" id="remember_me" name="remember">
                        <label for="remember_me">
                            Remember Me
                        </label>
                    </div>
                </div>
                <div class="col-4">
                    <button type="submit" class="btn btn-primary btn-block">Sign In</button>
                </div>
                </div>
        </form>

        <p class="mb-1 mt-3">
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}">I forgot my password</a>
            @endif
        </p>
        <p class="mb-0">
            @if (Route::has('register'))
                <a href="{{ route('register') }}" class="text-center">Register a new membership</a>
            @endif
        </p>
    </div>
    </x-guest-layout>