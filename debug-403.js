// Debug script to help identify 403 Forbidden issues
// Run this in your browser console or as a separate script

console.log('=== API 403 Debugging Helper ===');

// 1. Check if token exists
const token = localStorage.getItem('auth_token');
console.log('1. Token check:', token ? 'Token exists' : 'No token found');
console.log('Token value:', token?.substring(0, 20) + '...' || 'N/A');

// 2. Test API health endpoint (no auth required)
async function testHealth() {
    try {
        const response = await fetch('/api/health');
        const data = await response.json();
        console.log('2. Health check:', response.status, data);
    } catch (error) {
        console.error('2. Health check failed:', error);
    }
}

// 3. Test authentication endpoint
async function testAuth() {
    if (!token) {
        console.log('3. Cannot test auth - no token available');
        return;
    }
    
    try {
        const response = await fetch('/api/user', {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });
        
        console.log('3. Auth test status:', response.status);
        
        if (response.ok) {
            const user = await response.json();
            console.log('3. User data:', user);
            console.log('3. User role:', user.role);
        } else {
            const error = await response.json();
            console.log('3. Auth error:', error);
        }
    } catch (error) {
        console.error('3. Auth test failed:', error);
    }
}

// 4. Test specific endpoint that's failing
async function testFailingEndpoint(endpoint = '/api/student/enrollments') {
    if (!token) {
        console.log('4. Cannot test endpoint - no token available');
        return;
    }
    
    try {
        const response = await fetch(endpoint, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        console.log('4. Endpoint test status:', response.status);
        
        const data = await response.json();
        console.log('4. Endpoint response:', data);
        
        if (response.status === 403) {
            console.log('4. 403 Details:');
            console.log('   - Message:', data.message);
            console.log('   - Required roles:', data.required_roles || 'Not specified');
            console.log('   - User roles:', data.user_roles || 'Not specified');
        }
        
    } catch (error) {
        console.error('4. Endpoint test failed:', error);
    }
}

// 5. Check current axios configuration
function checkAxiosConfig() {
    console.log('5. Axios defaults:');
    console.log('   - Authorization header:', window.axios?.defaults?.headers?.common?.Authorization || 'Not set');
    console.log('   - X-Requested-With:', window.axios?.defaults?.headers?.common?.['X-Requested-With'] || 'Not set');
    console.log('   - Base URL:', window.axios?.defaults?.baseURL || 'Not set');
}

// Run all tests
async function runAllTests() {
    await testHealth();
    await testAuth();
    await testFailingEndpoint(); // Change this to your failing endpoint
    checkAxiosConfig();
    
    console.log('\n=== Recommendations ===');
    if (!token) {
        console.log('❌ No token found - user needs to login');
    } else {
        console.log('✅ Token exists - check the endpoint test results above');
    }
}

// Execute tests
runAllTests();

// Export functions for manual testing
window.debugAPI = {
    testHealth,
    testAuth,
    testFailingEndpoint,
    checkAxiosConfig,
    runAllTests
};

console.log('\nYou can also run individual tests manually:');
console.log('- debugAPI.testHealth()');
console.log('- debugAPI.testAuth()');
console.log('- debugAPI.testFailingEndpoint("/api/your-endpoint")');
console.log('- debugAPI.checkAxiosConfig()');