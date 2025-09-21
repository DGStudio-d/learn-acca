// Test your authentication and endpoints
// Paste this in your browser console and update the values

async function testYourAPI() {
    console.log('=== Testing Your Specific Case ===');
    
    // 1. First, let's try to login (update with your credentials)
    const loginData = {
        email: 'your-email@example.com', // UPDATE THIS
        password: 'your-password'        // UPDATE THIS
    };
    
    try {
        console.log('1. Attempting login...');
        const loginResponse = await fetch('/api/auth/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(loginData)
        });
        
        const loginResult = await loginResponse.json();
        console.log('Login status:', loginResponse.status);
        console.log('Login response:', loginResult);
        
        if (loginResult.success && loginResult.data.token) {
            const token = loginResult.data.token;
            const user = loginResult.data.user;
            
            console.log('✅ Login successful!');
            console.log('User role:', user.role);
            console.log('Token:', token.substring(0, 20) + '...');
            
            // Store token
            localStorage.setItem('auth_token', token);
            
            // 2. Test the endpoint that was failing
            console.log('\n2. Testing the failing endpoint...');
            
            // UPDATE THIS to your failing endpoint
            const testEndpoint = '/api/student/enrollments'; // CHANGE THIS
            
            const testResponse = await fetch(testEndpoint, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });
            
            console.log('Endpoint status:', testResponse.status);
            const testResult = await testResponse.json();
            console.log('Endpoint response:', testResult);
            
            if (testResponse.status === 403) {
                console.log('\n❌ Still getting 403. Possible reasons:');
                console.log('- User role "' + user.role + '" not allowed for this endpoint');
                console.log('- Check the route middleware in routes/api.php');
                console.log('- Make sure the endpoint allows your role');
            } else if (testResponse.status === 200) {
                console.log('\n✅ Endpoint working! The issue was authentication.');
            }
            
        } else {
            console.log('❌ Login failed:', loginResult.message);
        }
        
    } catch (error) {
        console.error('Test failed:', error);
    }
}

// Run the test
// testYourAPI(); // Uncomment this after updating the credentials

console.log('Please update the email, password, and endpoint in the script above, then run:');
console.log('testYourAPI()');