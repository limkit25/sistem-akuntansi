<x-guest-layout>
    <div class="card-body login-card-body">
        <p class="login-box-msg">This is a secure area of the application. Please confirm your password before continuing.</p>
        
        <x-auth-validation-errors class="mb-4" :errors="$errors" />

        <form action="{{ route('password.confirm') }}" method="POST">
            @csrf
            
            <div class="input-group mb-3">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
                <div class="input-group-append">
                    <div class="input-group-text">
                        <span class="fas fa-lock"></span>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-block">Confirm</button>
                </div>
                </div>
        </form>
    </div>
    </x-guest-layout>