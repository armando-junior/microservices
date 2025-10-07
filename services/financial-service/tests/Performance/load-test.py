#!/usr/bin/env python3
"""
Load Testing Script for Sales Service using Locust
Install: pip install locust
Run: locust -f load-test.py --host=http://localhost:9003
"""

from locust import HttpUser, task, between
import json
import os

JWT_TOKEN = os.getenv('JWT_TOKEN', '')

class SalesServiceUser(HttpUser):
    wait_time = between(1, 3)  # Wait 1-3 seconds between tasks
    
    def on_start(self):
        """Called when a simulated user starts"""
        self.headers = {
            'Authorization': f'Bearer {JWT_TOKEN}',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    
    @task(3)
    def health_check(self):
        """Health check endpoint (most frequent)"""
        self.client.get('/api/health')
    
    @task(5)
    def list_customers(self):
        """List customers"""
        self.client.get('/api/v1/customers', headers=self.headers)
    
    @task(5)
    def list_orders(self):
        """List orders"""
        self.client.get('/api/v1/orders', headers=self.headers)
    
    @task(2)
    def create_customer(self):
        """Create a new customer"""
        payload = {
            'name': 'Load Test Customer',
            'email': f'load.test.{self.environment.stats.num_requests}@example.com',
            'phone': '11987654321',
            'document': '11144477735'
        }
        self.client.post(
            '/api/v1/customers',
            json=payload,
            headers=self.headers
        )
    
    @task(1)
    def create_order(self):
        """Create a new order"""
        # Use a fake customer ID for load testing
        payload = {
            'customer_id': '550e8400-e29b-41d4-a716-446655440000',
            'notes': 'Load test order'
        }
        response = self.client.post(
            '/api/v1/orders',
            json=payload,
            headers=self.headers
        )
        
        # Try to cancel some orders
        if response.status_code == 201:
            try:
                order_data = response.json()
                order_id = order_data.get('data', {}).get('id')
                if order_id:
                    self.client.post(
                        f'/api/v1/orders/{order_id}/cancel',
                        headers=self.headers
                    )
            except:
                pass

class AdminUser(HttpUser):
    """Simulates admin users with different behavior"""
    wait_time = between(2, 5)
    weight = 1  # Less frequent than regular users
    
    def on_start(self):
        self.headers = {
            'Authorization': f'Bearer {JWT_TOKEN}',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    
    @task
    def list_all_customers(self):
        """Admin listing all customers"""
        self.client.get('/api/v1/customers?per_page=50', headers=self.headers)
    
    @task
    def list_all_orders(self):
        """Admin listing all orders"""
        self.client.get('/api/v1/orders?per_page=50', headers=self.headers)

# Custom WebUI Configuration
class WebsiteUser(HttpUser):
    """Base configuration for all users"""
    host = "http://localhost:9003"

