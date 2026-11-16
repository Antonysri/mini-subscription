import axiosClient from './axiosClient';
import { Plan } from '../types';

export const planApi = {
  getPlans: async (): Promise<{ status: string; data: Plan[] }> => {
    const response = await axiosClient.get<{ status: string; data: Plan[] }>('/plans');
    return response.data;
  },
};
