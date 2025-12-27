import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

function formatMoney(cents) {
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: 'USD',
    }).format((cents ?? 0) / 100);
}

export default function CartShow({ cart }) {
    const flash = usePage().props.flash || {};
    const [quantities, setQuantities] = useState(() => {
        const map = {};
        for (const item of cart?.items || []) {
            map[item.id] = item.quantity;
        }
        return map;
    });

    const totalCents = useMemo(() => cart?.total_cents ?? 0, [cart]);

    const updateItem = (itemId) => {
        router.patch(
            route('cart.items.update', itemId),
            { quantity: Number(quantities[itemId] || 1) },
            { preserveScroll: true },
        );
    };

    const removeItem = (itemId) => {
        router.delete(route('cart.items.destroy', itemId), {
            preserveScroll: true,
        });
    };

    const checkout = () => {
        router.post(route('checkout.store'), {}, { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Cart
                </h2>
            }
        >
            <Head title="Cart" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {flash.success && (
                        <div className="mb-6 rounded-md border border-green-200 bg-green-50 p-4 text-green-800">
                            {flash.success}
                        </div>
                    )}

                    {(cart?.items || []).length === 0 ? (
                        <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                            <div className="text-gray-900">
                                Your cart is empty.
                            </div>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                                <div className="p-6">
                                    <div className="divide-y">
                                        {cart.items.map((item) => (
                                            <div
                                                key={item.id}
                                                className="flex flex-col gap-4 py-4 sm:flex-row sm:items-center sm:justify-between"
                                            >
                                                <div>
                                                    <div className="text-lg font-semibold text-gray-900">
                                                        {item.product.name}
                                                    </div>
                                                    <div className="mt-1 text-sm text-gray-600">
                                                        {formatMoney(
                                                            item.product
                                                                .price_cents,
                                                        )}{' '}
                                                        · Stock:{' '}
                                                        {
                                                            item.product
                                                                .stock_quantity
                                                        }
                                                    </div>
                                                </div>

                                                <div className="flex flex-wrap items-center gap-3">
                                                    <div className="flex items-center gap-2">
                                                        <label className="text-sm text-gray-700">
                                                            Qty
                                                        </label>
                                                        <input
                                                            type="number"
                                                            min={1}
                                                            className="w-24 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                            value={
                                                                quantities[
                                                                    item.id
                                                                ] ?? item.quantity
                                                            }
                                                            onChange={(e) =>
                                                                setQuantities(
                                                                    (prev) => ({
                                                                        ...prev,
                                                                        [item.id]:
                                                                            Number(
                                                                                e
                                                                                    .target
                                                                                    .value ||
                                                                                    1,
                                                                            ),
                                                                    }),
                                                                )
                                                            }
                                                        />
                                                    </div>

                                                    <button
                                                        type="button"
                                                        className="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500"
                                                        onClick={() =>
                                                            updateItem(item.id)
                                                        }
                                                    >
                                                        Update
                                                    </button>

                                                    <button
                                                        type="button"
                                                        className="rounded-md bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-500"
                                                        onClick={() =>
                                                            removeItem(item.id)
                                                        }
                                                    >
                                                        Remove
                                                    </button>

                                                    <div className="min-w-[120px] text-right text-sm font-semibold text-gray-900">
                                                        {formatMoney(
                                                            item.line_total_cents,
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>

                                    <div className="mt-6 flex items-center justify-between border-t pt-6">
                                        <div className="text-lg font-semibold text-gray-900">
                                            Total
                                        </div>
                                        <div className="text-lg font-bold text-gray-900">
                                            {formatMoney(totalCents)}
                                        </div>
                                    </div>

                                    <div className="mt-6 flex justify-end">
                                        <button
                                            type="button"
                                            className="rounded-md bg-gray-900 px-5 py-2.5 text-sm font-medium text-white hover:bg-gray-800"
                                            onClick={checkout}
                                        >
                                            Checkout
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div className="text-sm text-gray-600">
                                Checkout will create an order, decrement product
                                stock, and clear your cart.
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}