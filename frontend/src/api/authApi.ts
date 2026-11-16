import axiosClient from './axiosClient';
import { AuthResponse, User } from '../types';

export const authApi = {
  login: async (email: string, password: string): Promise<AuthResponse> => {
    const response = await axiosClient.post<AuthResponse>('/login', { email, password });
    return response.data;
  },

  register: async (name: string, email: string, password: string, password_confirmation: string): Promise<AuthResponse> => {
    const response = await axiosClient.post<AuthResponse>('/register', {
      name,
      email,
      password,
      password_confirmation,
    });
    return response.data;
  },

  getMe: async (): Promise<{ success: boolean; data: User }> => {
    const response = await axiosClient.get<{ success: boolean; data: User }>('/me');
    return response.data;
  },

  logout: async (): Promise<{ success: boolean; message: string }> => {
    const response = await axiosClient.post<{ success: boolean; message: string }>('/logout');
    return response.data;
  },
};
