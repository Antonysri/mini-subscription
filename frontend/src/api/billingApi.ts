import axiosClient from './axiosClient';
import { Subscription, PaginatedPayments } from '../types';

export interface CheckoutResponse {
  success: boolean;
  message: string;
  data: {
    checkout_url: string;
    session_id: string;
    payment_id: number;
  };
}

export const billingApi = {
  createCheckout: async (plan_id: number): Promise<CheckoutResponse> => {
    const response = await axiosClient.post<CheckoutResponse>('/checkout', {
      plan_id,
    });
    return response.data;
  },

  getSubscription: async (): Promise<{ status: string; data: Subscription | null }> => {
    const response = await axiosClient.get<{ status: string; data: Subscription | null }>('/me/subscription');
    return response.data;
  },

  getPayments: async (page = 1, per_page = 10): Promise<{ status: string; data: PaginatedPayments }> => {
    const response = await axiosClient.get<{ status: string; data: PaginatedPayments }>(`/me/payments?page=${page}&per_page=${per_page}`);
    return response.data;
  },
};
