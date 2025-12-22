# Frontend Integration Guide: Google OAuth Login

## Quick Summary

Your backend is ready! Now integrate this endpoint in your Next.js frontend:

**API Endpoint:** `POST /api/customer-google-login`

## Step-by-Step Frontend Implementation

### 1. Update Your NextAuth Google Provider Callback

In your NextAuth configuration (likely `app/api/auth/[...nextauth]/route.ts` or `pages/api/auth/[...nextauth].ts`):

```typescript
import NextAuth from "next-auth";
import GoogleProvider from "next-auth/providers/google";

export const authOptions = {
  providers: [
    GoogleProvider({
      clientId: process.env.GOOGLE_CLIENT_ID!,
      clientSecret: process.env.GOOGLE_CLIENT_SECRET!,
    }),
  ],
  callbacks: {
    async signIn({ user, account, profile }) {
      if (account?.provider === "google") {
        try {
          // Call your Laravel backend
          const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/api/customer-google-login`, {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "Accept": "application/json",
            },
            body: JSON.stringify({
              email: user.email,
              name: user.name,
              google_id: account.providerAccountId, // This is the Google user ID
              avatar: user.image,
              user_type: 3, // Customer type
            }),
          });

          const data = await response.json();

          if (data.success) {
            // Store the Laravel token in the user object
            user.backendToken = data.token;
            user.userId = data.user.id;
            user.firstName = data.user.first_name;
            user.lastName = data.user.last_name;
            user.userType = data.user.user_type;
            user.roles = data.user.roles;
            
            return true;
          } else {
            console.error("Backend login failed:", data.error);
            return false;
          }
        } catch (error) {
          console.error("Error calling backend:", error);
          return false;
        }
      }
      return true;
    },
    
    async jwt({ token, user }) {
      // Add backend token to JWT token
      if (user) {
        token.backendToken = user.backendToken;
        token.userId = user.userId;
        token.firstName = user.firstName;
        token.lastName = user.lastName;
        token.userType = user.userType;
        token.roles = user.roles;
      }
      return token;
    },
    
    async session({ session, token }) {
      // Add backend token to session
      if (token) {
        session.backendToken = token.backendToken;
        session.user.id = token.userId;
        session.user.firstName = token.firstName;
        session.user.lastName = token.lastName;
        session.user.userType = token.userType;
        session.user.roles = token.roles;
      }
      return session;
    },
  },
  pages: {
    signIn: "/auth/signin",
    error: "/auth/error",
  },
};

export default NextAuth(authOptions);
```

### 2. Update Your TypeScript Types

Create or update `types/next-auth.d.ts`:

```typescript
import { DefaultSession, DefaultUser } from "next-auth";
import { JWT } from "next-auth/jwt";

declare module "next-auth" {
  interface Session {
    backendToken?: string;
    user: {
      id?: string;
      firstName?: string;
      lastName?: string;
      userType?: string;
      roles?: string[];
    } & DefaultSession["user"];
  }

  interface User extends DefaultUser {
    backendToken?: string;
    userId?: string;
    firstName?: string;
    lastName?: string;
    userType?: string;
    roles?: string[];
  }
}

declare module "next-auth/jwt" {
  interface JWT {
    backendToken?: string;
    userId?: string;
    firstName?: string;
    lastName?: string;
    userType?: string;
    roles?: string[];
  }
}
```

### 3. Create API Helper for Backend Calls

Create `lib/api.ts`:

```typescript
import { getSession } from "next-auth/react";

export async function fetchWithAuth(url: string, options: RequestInit = {}) {
  const session = await getSession();
  
  if (!session?.backendToken) {
    throw new Error("Not authenticated");
  }

  const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": `Bearer ${session.backendToken}`,
    ...options.headers,
  };

  const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}${url}`, {
    ...options,
    headers,
  });

  if (!response.ok) {
    throw new Error(`API error: ${response.status}`);
  }

  return response.json();
}

// Example usage in your components:
// const profile = await fetchWithAuth('/api/my-profile');
```

### 4. Create a Sign-In Page Component

Create `app/auth/signin/page.tsx` (App Router) or `pages/auth/signin.tsx` (Pages Router):

```typescript
"use client"; // If using App Router

import { signIn } from "next-auth/react";
import { useRouter } from "next/navigation"; // or "next/router" for Pages Router

export default function SignIn() {
  const router = useRouter();

  const handleGoogleSignIn = async () => {
    try {
      const result = await signIn("google", {
        redirect: false,
        callbackUrl: "/dashboard", // Where to redirect after successful login
      });

      if (result?.ok) {
        router.push("/dashboard");
      } else {
        console.error("Sign in failed:", result?.error);
        alert("Sign in failed. Please try again.");
      }
    } catch (error) {
      console.error("Sign in error:", error);
      alert("An error occurred. Please try again.");
    }
  };

  return (
    <div className="flex min-h-screen items-center justify-center">
      <div className="w-full max-w-md space-y-8 p-8">
        <div className="text-center">
          <h2 className="text-3xl font-bold">Sign in to your account</h2>
          <p className="mt-2 text-gray-600">Continue with Google</p>
        </div>

        <button
          onClick={handleGoogleSignIn}
          className="w-full flex items-center justify-center gap-3 px-4 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
        >
          <svg className="w-5 h-5" viewBox="0 0 24 24">
            <path
              fill="#4285F4"
              d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
            />
            <path
              fill="#34A853"
              d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
            />
            <path
              fill="#FBBC05"
              d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
            />
            <path
              fill="#EA4335"
              d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
            />
          </svg>
          Continue with Google
        </button>

        {/* Optional: Add email/password login here */}
      </div>
    </div>
  );
}
```

### 5. Protect Your Routes

Create a middleware or component wrapper `app/dashboard/layout.tsx`:

```typescript
"use client";

import { useSession } from "next-auth/react";
import { useRouter } from "next/navigation";
import { useEffect } from "react";

export default function DashboardLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const { data: session, status } = useSession();
  const router = useRouter();

  useEffect(() => {
    if (status === "unauthenticated") {
      router.push("/auth/signin");
    }
  }, [status, router]);

  if (status === "loading") {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div>Loading...</div>
      </div>
    );
  }

  if (!session) {
    return null;
  }

  return (
    <div>
      {/* Your dashboard layout */}
      {children}
    </div>
  );
}
```

### 6. Use the Session in Your Components

Example component `app/dashboard/page.tsx`:

```typescript
"use client";

import { useSession } from "next-auth/react";
import { useEffect, useState } from "react";
import { fetchWithAuth } from "@/lib/api";

export default function Dashboard() {
  const { data: session } = useSession();
  const [profile, setProfile] = useState(null);

  useEffect(() => {
    async function loadProfile() {
      try {
        const data = await fetchWithAuth("/api/my-profile");
        setProfile(data.user);
      } catch (error) {
        console.error("Failed to load profile:", error);
      }
    }

    if (session?.backendToken) {
      loadProfile();
    }
  }, [session]);

  return (
    <div className="p-8">
      <h1 className="text-2xl font-bold">Welcome, {session?.user?.name}!</h1>
      
      <div className="mt-4">
        <img
          src={session?.user?.image || "/default-avatar.png"}
          alt="Profile"
          className="w-16 h-16 rounded-full"
        />
      </div>

      {profile && (
        <div className="mt-4 space-y-2">
          <p><strong>Email:</strong> {profile.email}</p>
          <p><strong>User Type:</strong> {profile.user_type}</p>
          <p><strong>Status:</strong> {profile.status}</p>
        </div>
      )}

      <button
        onClick={() => signOut()}
        className="mt-4 px-4 py-2 bg-red-500 text-white rounded"
      >
        Sign Out
      </button>
    </div>
  );
}
```

### 7. Environment Variables

Add to your `.env.local`:

```bash
# Google OAuth
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret

# NextAuth
NEXTAUTH_URL=http://localhost:3000
NEXTAUTH_SECRET=your-nextauth-secret-generate-this

# Backend API
NEXT_PUBLIC_API_URL=http://127.0.0.1:8000
```

To generate `NEXTAUTH_SECRET`:
```bash
openssl rand -base64 32
```

### 8. Install Required Packages

```bash
npm install next-auth
# or
yarn add next-auth
# or
pnpm add next-auth
```

## Complete Flow Diagram

```
User clicks "Sign in with Google"
         ↓
NextAuth redirects to Google
         ↓
User authorizes on Google
         ↓
Google redirects back with OAuth token
         ↓
NextAuth signIn callback triggered
         ↓
Frontend calls: POST /api/customer-google-login
  Body: { email, name, google_id, avatar, user_type: 3 }
         ↓
Backend (Laravel) receives request
         ↓
Backend checks if google_id exists
  → YES: Login existing user
  → NO: Check if email exists
    → YES: Link Google account to existing user
    → NO: Create new user
         ↓
Backend returns JWT token + user data
         ↓
Frontend stores token in NextAuth session
         ↓
User redirected to dashboard
         ↓
All API calls use the JWT token via Authorization header
```

## Testing Checklist

- [ ] Google OAuth configured in Google Console
- [ ] Environment variables set correctly
- [ ] NextAuth callback calls backend successfully
- [ ] Backend token stored in session
- [ ] Protected routes redirect to login
- [ ] API calls include Authorization header
- [ ] User profile loads correctly
- [ ] Sign out works properly

## Troubleshooting

### Issue: "Not authenticated" error
**Solution:** Check if `session.backendToken` exists in your session object.

### Issue: CORS errors
**Solution:** Update Laravel `config/cors.php`:
```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_origins' => ['http://localhost:3000'],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'supports_credentials' => true,
```

### Issue: Token not persisting
**Solution:** Ensure JWT callback is saving the token properly and session callback is exposing it.

### Issue: Google redirect not working
**Solution:** Add authorized redirect URI in Google Console:
- `http://localhost:3000/api/auth/callback/google`

## Example API Calls After Login

### Get User Profile
```typescript
const profile = await fetchWithAuth('/api/my-profile');
```

### Place Order
```typescript
const order = await fetchWithAuth('/api/place-order', {
  method: 'POST',
  body: JSON.stringify({
    items: [...],
    address_id: 1,
  }),
});
```

### Get Orders
```typescript
const orders = await fetchWithAuth('/api/my-order');
```

All these endpoints will automatically use the JWT token from your Google login!

## Security Notes

✅ **Token stored in HTTP-only session** (NextAuth handles this)
✅ **No token in localStorage** (more secure)
✅ **CSRF protection** via NextAuth
✅ **Token refreshes automatically** via NextAuth session

## Support

If you encounter issues:
1. Check browser console for errors
2. Check Network tab for API calls
3. Verify backend logs: `storage/logs/laravel-{date}.log`
4. Test backend endpoint directly with Postman/cURL first

---

**Your backend is ready! Just implement the frontend code above and you're good to go! 🚀**
