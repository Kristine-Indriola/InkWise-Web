@extends('layouts.customerprofile')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Main Content -->
            <div class="flex-1">
                <!-- Header with Logo and Help Link -->
                <div class="flex justify-between items-center mb-6">
                    <div class="flex items-center gap-3">
                        <!-- InkWise Logo -->
                        <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-lg">I</span>
                        </div>
                        <h1 class="text-2xl font-bold text-gray-800">Security Check</h1>
                    </div>
                    <a href="#" class="text-orange-500 hover:text-orange-600 text-sm font-medium transition duration-200">Need help?</a>
                </div>

                <!-- Main Security Card -->
                <div class="bg-white rounded-xl shadow-lg p-8 text-center border border-gray-100">
                    <!-- Shield Icon -->
                    <div class="flex justify-center mb-6">
                        <div class="w-20 h-20 bg-orange-500 rounded-full flex items-center justify-center shadow-lg">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Security Text -->
                    <h2 class="text-xl font-semibold text-gray-800 mb-6 leading-relaxed">
                        To protect your account security, please verify your identity with one of the methods below.
                    </h2>

                    <!-- Verify Button -->
                    <a href="{{ url('customer/profile/email-verification') }}" class="inline-flex items-center justify-center gap-3 w-full bg-orange-500 hover:bg-orange-600 text-white px-8 py-4 rounded-lg font-medium transition duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        Verify by Email Link
                    </a>
                </div>
            </div>

            <!-- Sidebar FAQ -->
            <div class="lg:w-80">
                <div class="sticky top-8 space-y-6">
                    <!-- FAQ Section -->
                    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Frequently Asked Questions
                            </h3>

                            <div class="space-y-4">
                                <!-- FAQ 1 -->
                                <div class="border-l-4 border-orange-500 pl-4">
                                    <h4 class="text-sm font-semibold text-gray-800 mb-2">
                                        Why am I asked to verify my account?
                                    </h4>
                                    <p class="text-gray-600 text-sm leading-relaxed">
                                        Your account security is important to us. We ask for additional verification to ensure only you can access your account.
                                    </p>
                                </div>

                                <!-- FAQ 2 -->
                                <div class="border-l-4 border-orange-500 pl-4">
                                    <h4 class="text-sm font-semibold text-gray-800 mb-2">
                                        What can I do if I am unable to verify my account?
                                    </h4>
                                    <p class="text-gray-600 text-sm leading-relaxed">
                                        Please contact our Customer Service team for assistance with account verification.
                                    </p>
                                </div>

                                <!-- FAQ 3 -->
                                <div class="border-l-4 border-orange-500 pl-4">
                                    <h4 class="text-sm font-semibold text-gray-800 mb-2">
                                        How long does verification take?
                                    </h4>
                                    <p class="text-gray-600 text-sm leading-relaxed">
                                        Email verification is typically instant. Check your inbox and spam folder.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Security Tips -->
                    <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl p-6 border border-orange-200">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-8 h-8 bg-orange-500 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                </svg>
                            </div>
                            <h4 class="font-semibold text-orange-800">Security Tips</h4>
                        </div>
                        <ul class="text-sm text-orange-700 space-y-2">
                            <li>• Never share your verification codes</li>
                            <li>• Use a strong, unique password</li>
                            <li>• Enable two-factor authentication</li>
                            <li>• Regularly monitor your account activity</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
