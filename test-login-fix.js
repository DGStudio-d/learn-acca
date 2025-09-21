// Test Login with Correct Credentials
// Copy and paste this in your browser console

async function testLogin() {
    console.log('🧪 Testing Login with Seeded User Credentials...');
    
    // Test all three user types
    const testUsers = [
        { email: 'admin@learnacademy.com', password: 'password', role: 'admin' },
        { email: 'teacher@learnacademy.com', password: 'password', role: 'teacher' },
        { email: 'student@learnacademy.com', password: 'password', role: 'student' }
    ];
    
    for (const user of testUsers) {
        console.log(`\n📧 Testing ${user.role} login...`);
        
        try {
            const response = await fetch('/api/auth/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    email: user.email,
                    password: user.password
                })
            });
            
            const result = await response.json();
            
            if (response.ok && result.success) {
                console.log(`✅ ${user.role} login SUCCESS!`);
                console.log(`   Token: ${result.data.token.substring(0, 20)}...`);
                console.log(`   User: ${result.data.user.name} (${result.data.user.role})`);
                
                // Store token for this user type
                localStorage.setItem(`${user.role}_token`, result.data.token);
                
                // Test a protected endpoint
                console.log(`   Testing protected endpoint...`);
                const testEndpoint = user.role === 'student' ? '/api/student/enrollments' : 
                                    user.role === 'teacher' ? '/api/teacher/profile' : 
                                    '/api/admin/users';
                                    
                const protectedResponse = await fetch(testEndpoint, {
                    headers: {
                        'Authorization': `Bearer ${result.data.token}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });
                
                if (protectedResponse.ok) {
                    console.log(`   ✅ Protected endpoint working!`);
                } else {
                    console.log(`   ⚠️  Protected endpoint returned: ${protectedResponse.status}`);
                }
                
            } else {
                console.log(`❌ ${user.role} login FAILED:`, result.message);
                if (result.errors) {
                    console.log('   Errors:', result.errors);
                }
            }
            
        } catch (error) {
            console.error(`💥 ${user.role} login ERROR:`, error.message);
        }
    }
    
    console.log('\n🏁 Login testing complete!');
    console.log('\n💡 Stored tokens:');
    console.log('   Admin token:', localStorage.getItem('admin_token')?.substring(0, 20) + '...' || 'None');
    console.log('   Teacher token:', localStorage.getItem('teacher_token')?.substring(0, 20) + '...' || 'None');
    console.log('   Student token:', localStorage.getItem('student_token')?.substring(0, 20) + '...' || 'None');
}

// Quick individual login functions
window.loginAsAdmin = async () => {
    const response = await fetch('/api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: 'admin@learnacademy.com', password: 'password' })
    });
    const result = await response.json();
    if (result.success) {
        localStorage.setItem('auth_token', result.data.token);
        axios.defaults.headers.common['Authorization'] = `Bearer ${result.data.token}`;
        console.log('✅ Logged in as Admin!');
    }
    return result;
};

window.loginAsTeacher = async () => {
    const response = await fetch('/api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: 'teacher@learnacademy.com', password: 'password' })
    });
    const result = await response.json();
    if (result.success) {
        localStorage.setItem('auth_token', result.data.token);
        axios.defaults.headers.common['Authorization'] = `Bearer ${result.data.token}`;
        console.log('✅ Logged in as Teacher!');
    }
    return result;
};

window.loginAsStudent = async () => {
    const response = await fetch('/api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: 'student@learnacademy.com', password: 'password' })
    });
    const result = await response.json();
    if (result.success) {
        localStorage.setItem('auth_token', result.data.token);
        axios.defaults.headers.common['Authorization'] = `Bearer ${result.data.token}`;
        console.log('✅ Logged in as Student!');
    }
    return result;
};

// Run the comprehensive test
testLogin();

console.log('\n📋 Available functions:');
console.log('- loginAsAdmin()');
console.log('- loginAsTeacher()');
console.log('- loginAsStudent()');
console.log('- testLogin() // Run full test again');