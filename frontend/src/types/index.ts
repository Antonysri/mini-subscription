export interface User {
  id: number;
  name: string;
  email: string;
}

export interface Plan {
  id: number;
  name: string;
  slug: string;
  stripe_price_id: string;
  price: string | number;
  currency: string;
  duration_in_days: number;
  is_active: boolean;
  features?: string[];
}

export interface Subscription {
  id: number;
  status: string;
  starts_at: string;
  expires_at: string;
  days_remaining: number;
  plan: Plan;
}

export interface Payment {
  id: number;
  stripe_payment_id: string;
  amount: string | number;
  currency: string;
  status: 'succeeded' | 'pending' | 'failed';
  created_at: string;
}

export interface PaginatedPayments {
  data: Payment[];
  current_page: number;
  last_page: number;
  total: number;
  per_page: number;
}

export interface AuthResponse {
  success: boolean;
  message: string;
  data: {
    access_token: string;
    token_type: string;
    expires_in: number;
    user: User;
  };
}
