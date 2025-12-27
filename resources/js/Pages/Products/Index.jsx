import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

function formatMoney(cents) {
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: 'USD',
    }).format((cents ?? 0) / 100);
}

function Pagination({ links }) {
    if (!links || links.length === 0) return null;

    return (
        <div className="mt-6 flex flex-wrap gap-2">
            {links.map((link) => (
                <Link
                    key={link.label}
                    href={link.url || '#'}
                    preserveScroll
                    className={[
                        'rounded border px-3 py-1 text-sm',
                        link.active
                            ? 'border-indigo-600 bg-indigo-600 text-white'
                            : 'border-gray-300 bg-white text-gray-700',
                        link.url ? '' : 'pointer-events-none opacity-50',
                    ].join(' ')}
                    dangerouslySetInnerHTML={{ __html: link.label }}
                />
            ))}
        </div>
    );
}

export default function ProductsIndex({ products }) {
    const user = usePage().props.auth?.user;

    const productList = useMemo(() => products?.data || [], [products]);

    const [quantities, setQuantities] = useState(() => {
        const initial = {};
        for (const p of productList) {
            initial[p.id] = 1;
        }
        return initial;
    });

    const [processingProductId, setProcessingProductId] = useState(null);
    const [lastErrorProductId, setLastErrorProductId] = useState(null);
    const [lastErrors, setLastErrors] = useState({});

    const addToCart = (productId) => {
        const quantity = Number(quantities[productId] || 1);

        setProcessingProductId(productId);
        setLastErrorProductId(null);
        setLastErrors({});

        router.post(
            route('cart.items.store'),
            { product_id: productId, quantity },
            {
                preserveScroll: true,
                onError: (errors) => {
                    setLastErrorProductId(productId);
                    setLastErrors(errors || {});
                },
                onFinish: () => setProcessingProductId(null),
            },
        );
    };

    const content = (
        <>
            <Head title="Products" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="mb-6 flex items-center justify-between">
                        <h2 className="text-2xl font-semibold leading-tight text-gray-800">
                            Products
                        </h2>

                        <div className="flex items-center gap-3">
                            {user ? (
                                <>
                                    <Link
                                        href={route('cart.show')}
                                        className="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
                                    >
                                        View Cart
                                    </Link>
                                </>
                            ) : (
                                <>
                                    <Link
                                        href={route('login')}
                                        className="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
                                    >
                                        Log in to shop
                                    </Link>
                                </>
                            )}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {(products?.data || []).map((product) => (
                            <div
                                key={product.id}
                                className="overflow-hidden rounded-lg bg-white p-6 shadow-sm ring-1 ring-black/5"
                            >
                                <div className="flex items-start justify-between gap-4">
                                    <div>
                                        <div className="text-lg font-semibold text-gray-900">
                                            {product.name}
                                        </div>
                                        <div className="mt-1 text-sm text-gray-600">
                                            {formatMoney(product.price_cents)}
                                        </div>
                                    </div>

                                    <div
                                        className={[
                                            'rounded-full px-3 py-1 text-xs font-medium',
                                            product.stock_quantity > 0
                                                ? 'bg-green-100 text-green-800'
                                                : 'bg-red-100 text-red-800',
                                        ].join(' ')}
                                    >
                                        Stock: {product.stock_quantity}
                                    </div>
                                </div>

                                <div className="mt-5 flex items-center gap-3">
                                    <label className="text-sm text-gray-700">
                                        Qty
                                    </label>
                                    <input
                                        type="number"
                                        min={1}
                                        className="w-20 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        value={quantities[product.id] ?? 1}
                                        onChange={(e) =>
                                            setQuantities((prev) => ({
                                                ...prev,
                                                [product.id]: Number(
                                                    e.target.value || 1,
                                                ),
                                            }))
                                        }
                                    />

                                    <button
                                        type="button"
                                        className="flex-1 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50"
                                        disabled={
                                            !user ||
                                            product.stock_quantity <= 0 ||
                                            processingProductId === product.id
                                        }
                                        onClick={() => addToCart(product.id)}
                                    >
                                        Add to cart
                                    </button>
                                </div>

                                {lastErrorProductId === product.id &&
                                    (lastErrors?.quantity ||
                                        lastErrors?.product_id ||
                                        lastErrors?.cart) && (
                                        <div className="mt-3 text-sm text-red-600">
                                            {lastErrors.quantity ||
                                                lastErrors.product_id ||
                                                lastErrors.cart}
                                        </div>
                                    )}

                                {!user && (
                                    <div className="mt-3 text-sm text-gray-600">
                                        Please{' '}
                                        <Link
                                            href={route('login')}
                                            className="text-indigo-600 underline"
                                        >
                                            log in
                                        </Link>{' '}
                                        to add items to your cart.
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>

                    <Pagination links={products?.links} />
                </div>
            </div>
        </>
    );

    if (user) {
        return (
            <AuthenticatedLayout
                header={
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Products
                    </h2>
                }
            >
                {content}
            </AuthenticatedLayout>
        );
    }

    return <div className="min-h-screen bg-gray-100">{content}</div>;
}