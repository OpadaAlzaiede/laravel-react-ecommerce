<x-mail::message>
<h1 style="text-align: center; font-size: 24px;">
    Payment was Completed Successfully.
</h1>
@foreach($orders as $order)
<x-mail::table>
    <table>
        <tbody>
            <tr>
                <td>Seller</td>
                <td>
                    <a href="{{ url('/') }}">
                        {{ $order->vendorUser->vendor->store_name }}
                    </a>
                </td>
            </tr>
            <tr>
                <td>Order #</td>
                <td> {{ $order->id }}</td>
            </tr>
            <tr>
                <td>Items</td>
                <td> {{$order->orderItem->count()}} </td>
            </tr>
            <tr>
                <td>Total</td>
                <td> {{\Illuminate\Support\Number::currency($order->total_price)}} </td>
            </tr>
        </tbody>
    </table>
</x-mail::table>

<x-mail::table>
    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Quantity</th>
                <th>Price</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->orderItem as $item)
                <tr>
                    <td>
                        <table>
                            <tbody>
                                <tr>
                                    <td padding="5" style="padding: 5px">
                                        <img style="min-width: 60px; max-width: 60px;" src="{{ $item->product->getImageForOptions($item->variation_type_option_ids) }}" alt="{{ $item->product->name }}">
                                    </td>
                                    <td style="font-size: 13px; padding:5px;">
                                        {{ $item->product->title }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                    <td>
                        {{ $item->quantity }}
                    </td>
                    <td>
                        {{ \Illuminate\Support\Number::currency($item->price) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</x-mail::table>

<x-mail::button :url="$order->id">
    View Order Details
</x-mail::button>
@endforeach

<x-mail::subcopy>
    Lorem ipsum, dolor sit amet consectetur adipisicing elit. Quisquam architecto,
    fuga porro mollitia quo ratione ipsa voluptate voluptas velit, magni,
    est illum tempore atque sunt natus! Cumque ullam perferendis recusandae!
</x-mail::subcopy>

<x-mail::panel>
    Lorem ipsum dolor sit amet consectetur adipisicing elit.
    Voluptas minus omnis cumque libero exercitationem quo dicta quas odio placeat,
    ut facere esse excepturi iusto corrupti quod odit laborum dolorem voluptatem!
</x-mail::panel>

Thanks, <br>
{{ config('app.name') }}
</x-mail::message>
