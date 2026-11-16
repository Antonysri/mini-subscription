import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { billingApi } from '../api/billingApi';
import { useAppSelector } from '../store';
import {
  CreditCard,
  Calendar,
  Clock,
  CheckCircle2,
  AlertCircle,
  ArrowRight,
  Receipt,
  ChevronLeft,
  ChevronRight,
  ShieldCheck,
} from 'lucide-react';

export const DashboardPage: React.FC = () => {
  const { user } = useAppSelector((state) => state.auth);
  const [page, setPage] = useState(1);

  // 1. Fetch Active Subscription
  const {
    data: subData,
    isLoading: isSubLoading,
  } = useQuery({
    queryKey: ['my-subscription'],
    queryFn: billingApi.getSubscription,
    refetchOnWindowFocus: true,
  });

  // 2. Fetch Paginated Payment History
  const {
    data: paymentsData,
    isLoading: isPaymentsLoading,
  } = useQuery({
    queryKey: ['my-payments', page],
    queryFn: () => billingApi.getPayments(page, 5),
    refetchOnWindowFocus: true,
  });

  const subscription = subData?.data;
  const payments = paymentsData?.data?.data || [];
  const totalPages = paymentsData?.data?.last_page || 1;

  return (
    <div className="min-h-screen bg-slate-50/50 py-10 px-4 sm:px-6 lg:px-8">
      <div className="max-w-6xl mx-auto space-y-8">
        {/* Welcome Header */}
        <div className="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200/80 shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
          <div>
            <div className="flex items-center gap-2 text-xs font-bold text-brand-purple uppercase tracking-wider">
              <ShieldCheck className="w-4 h-4" />
              Customer Portal & Billing
            </div>
            <h1 className="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">
              Welcome, {user?.name || 'Subscriber'}
            </h1>
            <p className="text-sm text-slate-500 mt-0.5">{user?.email}</p>
          </div>

          <Link
            to="/plans"
            className="inline-flex items-center gap-2 bg-brand-purple hover:bg-brand-purple-dark text-white font-bold text-xs sm:text-sm uppercase tracking-wider py-2.5 px-5 rounded-lg shadow-sm shadow-brand-purple/20 transition-all"
          >
            Explore Plans
            <ArrowRight className="w-4 h-4" />
          </Link>
        </div>

        {/* Active Subscription Section */}
        <div className="space-y-4">
          <h2 className="text-lg font-bold text-slate-900 tracking-tight flex items-center gap-2">
            <CreditCard className="w-5 h-5 text-brand-purple" />
            Active Subscription
          </h2>

          {isSubLoading ? (
            <div className="bg-white rounded-2xl p-8 border border-slate-200 text-center text-slate-400 animate-pulse">
              Loading subscription details...
            </div>
          ) : subscription ? (
            /* Subscribed State Card */
            <div className="bg-white rounded-2xl border-2 border-brand-purple/20 p-6 sm:p-8 shadow-md shadow-brand-purple/5 relative overflow-hidden">
              <div className="absolute top-0 right-0 w-32 h-32 bg-brand-purple/5 rounded-bl-full pointer-events-none" />

              <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div>
                  <div className="flex items-center gap-3">
                    <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase tracking-wide">
                      <CheckCircle2 className="w-3.5 h-3.5" />
                      {subscription.status}
                    </span>
                    <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-purple-50 text-brand-purple border border-purple-200">
                      <Clock className="w-3.5 h-3.5" />
                      {subscription.days_remaining} Days Remaining
                    </span>
                  </div>

                  <h3 className="text-2xl font-black text-slate-900 mt-3">
                    {subscription.plan.name}
                  </h3>
                  <p className="text-sm font-semibold text-slate-500 mt-1">
                    ${subscription.plan.price} / {subscription.plan.duration_in_days} Days Access
                  </p>
                </div>

                <div className="grid grid-cols-2 gap-4 sm:gap-6 bg-slate-50 p-4 rounded-xl border border-slate-100">
                  <div>
                    <div className="text-xs font-medium text-slate-400 flex items-center gap-1">
                      <Calendar className="w-3.5 h-3.5" />
                      Started On
                    </div>
                    <div className="text-xs sm:text-sm font-bold text-slate-800 mt-1">
                      {new Date(subscription.starts_at).toLocaleDateString()}
                    </div>
                  </div>
                  <div>
                    <div className="text-xs font-medium text-slate-400 flex items-center gap-1">
                      <Clock className="w-3.5 h-3.5" />
                      Expires On
                    </div>
                    <div className="text-xs sm:text-sm font-bold text-slate-800 mt-1">
                      {new Date(subscription.expires_at).toLocaleDateString()}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          ) : (
            /* Unsubscribed / Empty State Card */
            <div className="bg-white rounded-2xl p-8 border border-slate-200 text-center space-y-4 shadow-sm">
              <div className="inline-flex w-14 h-14 rounded-full bg-slate-100 items-center justify-center text-slate-400">
                <AlertCircle className="w-7 h-7" />
              </div>
              <div>
                <h3 className="text-lg font-bold text-slate-800">
                  No Active Subscription Found
                </h3>
                <p className="text-sm text-slate-500 max-w-md mx-auto mt-1">
                  You are currently on the free tier. Choose an upgrade plan to access all premium features!
                </p>
              </div>
              <Link
                to="/plans"
                className="inline-flex items-center gap-2 bg-brand-purple hover:bg-brand-purple-dark text-white font-bold text-xs sm:text-sm uppercase tracking-wider py-2.5 px-6 rounded-lg shadow transition-all"
              >
                View Pricing Plans
                <ArrowRight className="w-4 h-4" />
              </Link>
            </div>
          )}
        </div>

        {/* Payment History Section */}
        <div className="space-y-4">
          <h2 className="text-lg font-bold text-slate-900 tracking-tight flex items-center gap-2">
            <Receipt className="w-5 h-5 text-brand-purple" />
            Payment History & Receipts
          </h2>

          <div className="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            {isPaymentsLoading ? (
              <div className="p-8 text-center text-slate-400 animate-pulse">
                Loading payment records...
              </div>
            ) : payments.length === 0 ? (
              <div className="p-8 text-center text-slate-400 text-sm">
                No payment transactions recorded yet.
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-slate-100">
                  <thead className="bg-slate-50/70">
                    <tr>
                      <th className="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                        Transaction Reference
                      </th>
                      <th className="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                        Amount
                      </th>
                      <th className="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                        Status
                      </th>
                      <th className="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                        Date & Time
                      </th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100 bg-white text-sm">
                    {payments.map((payment) => (
                      <tr key={payment.id} className="hover:bg-slate-50/50 transition-colors">
                        <td className="px-6 py-4 whitespace-nowrap font-mono text-xs text-slate-600">
                          {payment.stripe_payment_id || `PAY-${payment.id}`}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap font-bold text-slate-900">
                          ${parseFloat(String(payment.amount)).toFixed(2)} {payment.currency?.toUpperCase()}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <span
                            className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase ${
                              payment.status === 'succeeded'
                                ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                : payment.status === 'pending'
                                ? 'bg-amber-50 text-amber-700 border border-amber-200'
                                : 'bg-rose-50 text-rose-700 border border-rose-200'
                            }`}
                          >
                            {payment.status}
                          </span>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-slate-500 text-xs">
                          {new Date(payment.created_at).toLocaleString()}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}

            {/* Pagination Controls */}
            {totalPages > 1 && (
              <div className="px-6 py-3.5 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                <span className="text-xs text-slate-500 font-medium">
                  Page {page} of {totalPages}
                </span>
                <div className="flex gap-2">
                  <button
                    onClick={() => setPage((p) => Math.max(p - 1, 1))}
                    disabled={page === 1}
                    className="p-1.5 rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40 hover:bg-slate-100"
                  >
                    <ChevronLeft className="w-4 h-4" />
                  </button>
                  <button
                    onClick={() => setPage((p) => Math.min(p + 1, totalPages))}
                    disabled={page === totalPages}
                    className="p-1.5 rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40 hover:bg-slate-100"
                  >
                    <ChevronRight className="w-4 h-4" />
                  </button>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default DashboardPage;
