import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { planApi } from '../api/planApi';
import { billingApi } from '../api/billingApi';
import { useAppSelector } from '../store';
import { Loader2 } from 'lucide-react';

interface PricingTier {
  id: number;
  name: string;
  price: string;
  monthlyPrice: string;
  isPopular?: boolean;
  features: string[];
}

export const PricingPage: React.FC = () => {
  const [billingCycle, setBillingCycle] = useState<'yearly' | 'monthly'>('yearly');
  const [loadingPlanId, setLoadingPlanId] = useState<number | null>(null);
  const [checkoutError, setCheckoutError] = useState<string | null>(null);

  const { isAuthenticated } = useAppSelector((state) => state.auth);
  const navigate = useNavigate();

  // Fetch cached plans from backend API using TanStack Query
  const { data: plansData, isLoading } = useQuery({
    queryKey: ['plans'],
    queryFn: planApi.getPlans,
    staleTime: 1000 * 60 * 5, // 5 min cache
  });

  // Dynamically map backend plans with the mockup styling & feature lists
  const backendPlans = plansData?.data || [];
  
  const tiers: PricingTier[] = [
    {
      id: backendPlans[0]?.id || 1,
      name: backendPlans[0]?.name || 'Personal',
      price: '$49',
      monthlyPrice: `$${backendPlans[0] ? Math.round(Number(backendPlans[0].price)) : 9}`,
      features: [
        '1 GB SSD Storage',
        '50 GB Data Transfer',
        '512 MB Ram Limit',
        '20 Entry Process',
        '20 MB/s I/O Limit',
        '10 Emails, Database',
        'Single Website',
        'Free SSI certificate',
      ],
    },
    {
      id: backendPlans[1]?.id || 2,
      name: backendPlans[1]?.name || 'Startup',
      price: '$69',
      monthlyPrice: `$${backendPlans[1] ? Math.round(Number(backendPlans[1].price)) : 29}`,
      isPopular: true,
      features: [
        '5 GB SSD Storage',
        '250 GB Data Transfer',
        '1 GB Ram Limit',
        '100 Entry Process',
        '50 MB/s I/O Limit',
        '50 Emails, Database',
        '4 Website',
        'Free SSI certificate',
      ],
    },
    {
      id: backendPlans[2]?.id || 3,
      name: backendPlans[2]?.name || 'Corporate',
      price: '$99',
      monthlyPrice: `$${backendPlans[2] ? Math.round(Number(backendPlans[2].price)) : 99}`,
      features: [
        '10 GB SSD Storage',
        '500 GB Data Transfer',
        '2 GB Ram Limit',
        '200 Entry Process',
        '20 MB/s I/O Limit',
        '100 Emails, Database',
        '20 Website',
        'Free SSI certificate',
      ],
    },
  ];

  const handleBuyNow = async (planId: number) => {
    if (!isAuthenticated) {
      // Redirect to login if unauthenticated
      navigate('/login', { state: { returnTo: '/plans', planId } });
      return;
    }

    try {
      setLoadingPlanId(planId);
      setCheckoutError(null);
      const response = await billingApi.createCheckout(planId);
      if (response.checkout_url) {
        window.location.href = response.checkout_url;
      }
    } catch (err: unknown) {
      const errorMsg =
        err && typeof err === 'object' && 'response' in err
          ? (err as { response?: { data?: { message?: string } } }).response?.data?.message || 'Failed to initialize checkout session.'
          : 'Network error initializing checkout.';
      setCheckoutError(errorMsg);
    } finally {
      setLoadingPlanId(null);
    }
  };

  return (
    <div className="min-h-screen bg-white py-12 px-4 sm:px-6 lg:px-8 flex flex-col items-center">
      {/* Header section matching pricing_template.png */}
      <div className="text-center max-w-2xl mx-auto">
        <h1 className="text-3xl sm:text-4xl font-extrabold text-slate-800 tracking-wider uppercase">
          PRICE PLAN
        </h1>
        <p className="mt-3 text-sm sm:text-base text-slate-500 font-medium">
          Choose a best plan for you and your organization
        </p>

        {/* Toggle Pill Switch */}
        <div className="mt-6 inline-flex p-1 bg-slate-100/90 rounded-xl border border-slate-200">
          <button
            type="button"
            onClick={() => setBillingCycle('yearly')}
            className={`px-5 py-2 text-xs sm:text-sm font-semibold rounded-lg transition-all ${
              billingCycle === 'yearly'
                ? 'bg-white text-slate-800 shadow-sm'
                : 'text-slate-500 hover:text-slate-800'
            }`}
          >
            Yearly Plan
          </button>
          <button
            type="button"
            onClick={() => setBillingCycle('monthly')}
            className={`px-5 py-2 text-xs sm:text-sm font-semibold rounded-lg transition-all ${
              billingCycle === 'monthly'
                ? 'bg-white text-slate-800 shadow-sm'
                : 'text-slate-500 hover:text-slate-800'
            }`}
          >
            Monthly Plan
          </button>
        </div>
      </div>

      {checkoutError && (
        <div className="mt-6 max-w-md w-full bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-lg text-sm text-center">
          {checkoutError}
        </div>
      )}

      {/* Pricing Cards Grid matching pricing_template.png */}
      <div className="mt-12 max-w-6xl w-full grid grid-cols-1 md:grid-cols-3 gap-8 items-stretch">
        {tiers.map((tier) => {
          const isFeatured = tier.isPopular;
          const displayPrice = billingCycle === 'yearly' ? tier.price : tier.monthlyPrice;
          const cycleLabel = billingCycle === 'yearly' ? 'Yearly Plan' : 'Monthly Plan';

          if (isFeatured) {
            // 🌟 FEATURED STARTUP CARD (Vibrant Purple + Asymmetric bottom-right curve + Yellow POPULAR badge)
            return (
              <div
                key={tier.name}
                className="relative bg-brand-purple text-white rounded-2xl rounded-br-[56px] shadow-2xl shadow-brand-purple/30 p-8 flex flex-col justify-between border border-brand-purple-dark md:-translate-y-2 transition-transform hover:-translate-y-3"
              >
                {/* Yellow POPULAR Badge */}
                <div className="absolute top-4 left-6">
                  <span className="bg-amber-400 text-slate-900 text-[10px] font-black uppercase px-2.5 py-0.5 rounded-full shadow-sm tracking-wider">
                    POPULAR
                  </span>
                </div>

                <div className="text-center pt-4">
                  <h3 className="text-xl font-bold text-white tracking-wide">
                    {tier.name}
                  </h3>
                  <p className="text-xs font-medium text-purple-200 mt-1">
                    {cycleLabel}
                  </p>

                  <div className="my-6">
                    <span className="text-4xl sm:text-5xl font-extrabold text-white tracking-tight">
                      {displayPrice}
                    </span>
                  </div>

                  {/* Features List */}
                  <div className="space-y-2.5 my-6 text-xs sm:text-sm font-medium text-purple-100">
                    {tier.features.map((feature, idx) => (
                      <div key={idx} className="leading-relaxed">
                        {feature}
                      </div>
                    ))}
                  </div>
                </div>

                {/* Solid White BUY NOW Button */}
                <div className="pt-4">
                  <button
                    onClick={() => handleBuyNow(tier.id)}
                    disabled={loadingPlanId === tier.id || isLoading}
                    className="w-full bg-white text-brand-purple hover:bg-purple-50 font-bold uppercase text-xs sm:text-sm tracking-wider py-2.5 px-6 rounded-lg shadow-md transition-all flex items-center justify-center gap-2"
                  >
                    {loadingPlanId === tier.id ? (
                      <>
                        <Loader2 className="w-4 h-4 animate-spin text-brand-purple" />
                        Redirecting...
                      </>
                    ) : (
                      'BUY NOW'
                    )}
                  </button>
                </div>
              </div>
            );
          }

          // 🌟 REGULAR CARD (Personal & Corporate: White background, purple text, outline button)
          return (
            <div
              key={tier.name}
              className="bg-white rounded-2xl border border-slate-200/90 shadow-lg shadow-slate-100 p-8 flex flex-col justify-between transition-transform hover:-translate-y-1"
            >
              <div className="text-center">
                <h3 className="text-xl font-bold text-brand-purple tracking-wide">
                  {tier.name}
                </h3>
                <p className="text-xs font-medium text-slate-400 mt-1">
                  {cycleLabel}
                </p>

                <div className="my-6">
                  <span className="text-4xl sm:text-5xl font-extrabold text-brand-purple tracking-tight">
                    {displayPrice}
                  </span>
                </div>

                {/* Features List */}
                <div className="space-y-2.5 my-6 text-xs sm:text-sm font-medium text-slate-600">
                  {tier.features.map((feature, idx) => (
                    <div key={idx} className="leading-relaxed">
                      {feature}
                    </div>
                  ))}
                </div>
              </div>

              {/* Purple Outline BUY NOW Button */}
              <div className="pt-4">
                <button
                  onClick={() => handleBuyNow(tier.id)}
                  disabled={loadingPlanId === tier.id || isLoading}
                  className="w-full border-2 border-brand-purple text-brand-purple hover:bg-brand-purple hover:text-white font-bold uppercase text-xs sm:text-sm tracking-wider py-2.5 px-6 rounded-lg transition-all flex items-center justify-center gap-2"
                >
                  {loadingPlanId === tier.id ? (
                    <>
                      <Loader2 className="w-4 h-4 animate-spin" />
                      Redirecting...
                    </>
                  ) : (
                    'BUY NOW'
                  )}
                </button>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
};

export default PricingPage;
