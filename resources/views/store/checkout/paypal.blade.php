@extends('store.layout')

@section('title', __('Review & Checkout'))

@section('content')
    <main class="bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-extrabold py-8">{{ __('Review & Checkout') }}</h1>
            <div class="flex flex-col gap-4 mb-4 -mt-4">
                @include('components.alert-success')
                @include('components.alert-danger')
            </div>
            <div class="max-w-2xl pb-24 mx-auto lg:max-w-none">
                <div class="lg:grid lg:grid-cols-2 lg:gap-x-12">
                    <div>
                        @include('store.checkout.partials._customer_billing_info')
                    </div>
                    <!-- Order summary -->
                    <div class="rounded bg-white shadow-md border border-gray-100 mt-10 pb-4 lg:mt-0">
                        <div class="p-4 lg:p-6">
                            @include('store.checkout.partials._order_summary')
                            <div class="mt-4">
                                <div class="flex flex-col mt-6">
                                    <div id="paypal-button-container" class="mt-4 w-full"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
@push('scripts')
    {{--This api can't be hosted on localhost--}}
    <script src="https://www.paypal.com/sdk/js?client-id={{ $client_id }}&currency={{ $currency }}"></script>
    <script>
        paypal.Buttons({
            // Sets up the transaction when a payment button is clicked
            createOrder: (data, actions) => {
                return actions.order.create({
                    "purchase_units": [{
                        "reference_id": "{{ $payment_id }}",
                        "currency_code": "USD",
                        "description": "{{ $order['items'][0]['name'] }}",
                        "amount": {
                            "value": "{{ $order['total'] }}"
                        }
                    }],
                });
            },
            onApprove: (data, actions) => {
                return actions.order.capture().then(function(orderData) {
                    const element = document.getElementById('paypal-button-container');
                    element.innerHTML = '<h3>Processing...</h3>';
                    fetch('{{ $payment_callback_url }}', {
                        method: 'POST',
                        body: JSON.stringify(orderData),
                        headers: {
                            'Content-type': 'application/json; charset=UTF-8',
                        }
                    });
                    window.location.replace('{{ $payment_success_url }}');
                });
            },
            onError: (err, actions) => {
                actions.redirect('{{ $payment_failed_url }}')
            },
        }).render('#paypal-button-container');
    </script>
@endpush
