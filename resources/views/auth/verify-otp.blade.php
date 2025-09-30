<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        Enter the 6-digit code sent to your email.
    </div>

    @if (session('status'))
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ session('status') }}
        </div>
    @endif

    <!-- Triggered automatically on page load -->
    <button type="button" id="openOtpModalBtn" class="hidden" data-bs-toggle="modal" data-bs-target="#otpModal"></button>

    <!-- OTP Modal -->
    <div class="modal fade" id="otpModal" tabindex="-1" aria-labelledby="otpModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="otpModalLabel">Verify Email</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="otpForm">
                        @csrf
                        <div class="mb-3">
                            <label for="code" class="form-label">Verification Code</label>
                            <input type="text" id="code" name="code" class="form-control" required maxlength="6" />
                            <div class="invalid-feedback" id="codeError"></div>
                        </div>
                    </form>
                    <div class="text-end">
                        <a href="{{ route('verification.otp.send') }}" id="resendLink">Resend code</a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="verifyBtn">
                        <span class="spinner-border spinner-border-sm d-none" id="otpSpinner" role="status" aria-hidden="true"></span>
                        Verify
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function(){
            function ajaxVerify(code){
                const spinner = document.getElementById('otpSpinner');
                spinner.classList.remove('d-none');
                document.getElementById('verifyBtn').disabled = true;
                fetch('{{ route("verification.otp.verify") }}', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: new URLSearchParams({ code })
                })
                .then(async r => {
                    if (r.ok) return { ok: true };
                    let data = {};
                    try { data = await r.json(); } catch(e) {}
                    throw data;
                })
                .then(() => { window.location.href = '{{ route("login") }}'; })
                .catch(err => {
                    const msg = (err && err.errors && err.errors.code && err.errors.code[0]) || 'Invalid code';
                    const input = document.getElementById('code');
                    const fb = document.getElementById('codeError');
                    input.classList.add('is-invalid');
                    fb.textContent = msg;
                })
                .finally(() => {
                    spinner.classList.add('d-none');
                    document.getElementById('verifyBtn').disabled = false;
                });
            }

            function ready(){
                const trigger = document.getElementById('openOtpModalBtn');
                if (trigger) trigger.click();

                document.getElementById('verifyBtn').addEventListener('click', function(){
                    const input = document.getElementById('code');
                    input.classList.remove('is-invalid');
                    const code = input.value.trim();
                    if (!code || code.length !== 6) {
                        input.classList.add('is-invalid');
                        document.getElementById('codeError').textContent = 'Please enter the 6-digit code.';
                        return;
                    }
                    ajaxVerify(code);
                });
            }
            if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready); else ready();
        })();
    </script>
</x-guest-layout>


