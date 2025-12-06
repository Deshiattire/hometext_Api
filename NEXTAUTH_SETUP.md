# NextAuth.js v5 Setup Guide for Laravel Sanctum

This guide will help you integrate NextAuth.js v5 (Auth.js) with your Laravel Sanctum backend.

## Prerequisites

- Next.js 16 application
- Laravel backend with Sanctum configured
- Backend API URL configured

## Installation

```bash
npm install next-auth@beta
# or
yarn add next-auth@beta
# or
pnpm add next-auth@beta
```

## Configuration

### 1. Create NextAuth Configuration

Create `app/api/auth/[...nextauth]/route.ts`:

```typescript
import NextAuth, { NextAuthOptions } from "next-auth"
import CredentialsProvider from "next-auth/providers/credentials"

export const authOptions: NextAuthOptions = {
  providers: [
    CredentialsProvider({
      name: "Credentials",
      credentials: {
        email: { label: "Email", type: "text" },
        password: { label: "Password", type: "password" },
        user_type: { label: "User Type", type: "number" }
      },
      async authorize(credentials) {
        if (!credentials?.email || !credentials?.password) {
          return null
        }

        try {
          // Login to Laravel backend
          const res = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/api/login`, {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "Accept": "application/json",
            },
            body: JSON.stringify({
              email: credentials.email,
              password: credentials.password,
              user_type: credentials.user_type || 3, // Default to customer
            }),
          })

          if (!res.ok) {
            return null
          }

          const user = await res.json()

          if (user && user.token) {
            return {
              id: user.id || credentials.email,
              email: user.email,
              name: user.name,
              token: user.token,
              role: user.role,
              roles: user.roles || [],
              permissions: user.permissions || [],
              phone: user.phone,
              photo: user.photo,
            }
          }

          return null
        } catch (error) {
          console.error("Auth error:", error)
          return null
        }
      },
    }),
  ],
  callbacks: {
    async jwt({ token, user }) {
      if (user) {
        token.accessToken = user.token
        token.role = user.role
        token.roles = user.roles
        token.permissions = user.permissions
        token.phone = user.phone
        token.photo = user.photo
      }
      return token
    },
    async session({ session, token }) {
      if (session.user) {
        session.user.id = token.sub
        session.user.token = token.accessToken as string
        session.user.role = token.role as number
        session.user.roles = token.roles as string[]
        session.user.permissions = token.permissions as string[]
        session.user.phone = token.phone as string
        session.user.photo = token.photo as string
      }
      return session
    },
  },
  pages: {
    signIn: "/auth/login",
    // Add custom pages as needed
  },
  session: {
    strategy: "jwt",
    maxAge: 30 * 24 * 60 * 60, // 30 days
  },
  secret: process.env.NEXTAUTH_SECRET,
}

const handler = NextAuth(authOptions)

export { handler as GET, handler as POST }
```

### 2. Create Type Definitions

Create `types/next-auth.d.ts`:

```typescript
import "next-auth"
import "next-auth/jwt"

declare module "next-auth" {
  interface Session {
    user: {
      id: string
      email: string
      name: string
      token: string
      role: number
      roles: string[]
      permissions: string[]
      phone?: string
      photo?: string
    }
  }

  interface User {
    id: string
    email: string
    name: string
    token: string
    role: number
    roles: string[]
    permissions: string[]
    phone?: string
    photo?: string
  }
}

declare module "next-auth/jwt" {
  interface JWT {
    accessToken: string
    role: number
    roles: string[]
    permissions: string[]
    phone?: string
    photo?: string
  }
}
```

### 3. Environment Variables

Add to your `.env.local`:

```env
NEXT_PUBLIC_API_URL=http://localhost:8000
NEXTAUTH_URL=http://localhost:3000
NEXTAUTH_SECRET=your-secret-key-here
```

Generate a secret:
```bash
openssl rand -base64 32
```

### 4. Create API Client Helper

Create `lib/api-client.ts`:

```typescript
import { getSession } from "next-auth/react"

export async function apiClient(url: string, options: RequestInit = {}) {
  const session = await getSession()
  
  const headers: HeadersInit = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    ...options.headers,
  }

  if (session?.user?.token) {
    headers["Authorization"] = `Bearer ${session.user.token}`
  }

  const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/api${url}`, {
    ...options,
    headers,
  })

  if (!response.ok) {
    if (response.status === 401) {
      // Handle unauthorized - redirect to login
      window.location.href = "/auth/login"
    }
    throw new Error(`API Error: ${response.statusText}`)
  }

  return response.json()
}
```

### 5. Create Auth Hooks

Create `hooks/useAuth.ts`:

```typescript
import { useSession } from "next-auth/react"

export function useAuth() {
  const { data: session, status } = useSession()

  return {
    user: session?.user,
    isAuthenticated: !!session?.user,
    isLoading: status === "loading",
    hasRole: (role: string) => {
      return session?.user?.roles?.includes(role) ?? false
    },
    hasPermission: (permission: string) => {
      return session?.user?.permissions?.includes(permission) ?? false
    },
    hasAnyRole: (roles: string[]) => {
      return roles.some(role => session?.user?.roles?.includes(role))
    },
    hasAnyPermission: (permissions: string[]) => {
      return permissions.some(permission => 
        session?.user?.permissions?.includes(permission)
      )
    },
  }
}
```

### 6. Create Middleware for Route Protection

Create `middleware.ts` in the root:

```typescript
import { withAuth } from "next-auth/middleware"
import { NextResponse } from "next/server"

export default withAuth(
  function middleware(req) {
    const token = req.nextauth.token
    const path = req.nextUrl.pathname

    // Admin routes
    if (path.startsWith("/admin")) {
      if (!token?.roles?.includes("admin")) {
        return NextResponse.redirect(new URL("/unauthorized", req.url))
      }
    }

    // Sales manager routes
    if (path.startsWith("/sales")) {
      if (!token?.roles?.includes("sales_manager") && !token?.roles?.includes("admin")) {
        return NextResponse.redirect(new URL("/unauthorized", req.url))
      }
    }

    return NextResponse.next()
  },
  {
    callbacks: {
      authorized: ({ token }) => !!token,
    },
  }
)

export const config = {
  matcher: [
    "/admin/:path*",
    "/sales/:path*",
    "/dashboard/:path*",
  ],
}
```

### 7. Create Login Page

Create `app/auth/login/page.tsx`:

```typescript
"use client"

import { signIn } from "next-auth/react"
import { useState } from "react"
import { useRouter } from "next/navigation"

export default function LoginPage() {
  const router = useRouter()
  const [email, setEmail] = useState("")
  const [password, setPassword] = useState("")
  const [userType, setUserType] = useState(3) // 1=admin, 2=sales_manager, 3=customer
  const [error, setError] = useState("")
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError("")
    setLoading(true)

    try {
      const result = await signIn("credentials", {
        email,
        password,
        user_type: userType,
        redirect: false,
      })

      if (result?.error) {
        setError("Invalid credentials")
      } else {
        // Redirect based on user type
        if (userType === 1) {
          router.push("/admin/dashboard")
        } else if (userType === 2) {
          router.push("/sales/dashboard")
        } else {
          router.push("/dashboard")
        }
      }
    } catch (err) {
      setError("An error occurred. Please try again.")
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="min-h-screen flex items-center justify-center">
      <form onSubmit={handleSubmit} className="space-y-4 w-full max-w-md">
        <h1 className="text-2xl font-bold">Login</h1>
        
        {error && (
          <div className="bg-red-100 text-red-700 p-3 rounded">
            {error}
          </div>
        )}

        <div>
          <label>User Type</label>
          <select
            value={userType}
            onChange={(e) => setUserType(Number(e.target.value))}
            className="w-full p-2 border rounded"
          >
            <option value={3}>Customer</option>
            <option value={2}>Sales Manager</option>
            <option value={1}>Admin</option>
          </select>
        </div>

        <div>
          <label>Email/Phone</label>
          <input
            type="text"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            required
            className="w-full p-2 border rounded"
          />
        </div>

        <div>
          <label>Password</label>
          <input
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            required
            className="w-full p-2 border rounded"
          />
        </div>

        <button
          type="submit"
          disabled={loading}
          className="w-full bg-blue-500 text-white p-2 rounded hover:bg-blue-600"
        >
          {loading ? "Logging in..." : "Login"}
        </button>
      </form>
    </div>
  )
}
```

### 8. Wrap App with SessionProvider

Update `app/layout.tsx`:

```typescript
import { SessionProvider } from "next-auth/react"

export default function RootLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <html lang="en">
      <body>
        <SessionProvider>
          {children}
        </SessionProvider>
      </body>
    </html>
  )
}
```

## Usage Examples

### Protected Component

```typescript
"use client"

import { useAuth } from "@/hooks/useAuth"

export default function ProtectedComponent() {
  const { user, hasPermission } = useAuth()

  if (!user) {
    return <div>Please login</div>
  }

  if (!hasPermission("view products")) {
    return <div>You don't have permission</div>
  }

  return <div>Protected content</div>
}
```

### Making API Calls

```typescript
import { apiClient } from "@/lib/api-client"

// In a component or server action
const products = await apiClient("/products")
```

## Security Best Practices

1. **Token Storage**: Tokens are stored in JWT session (server-side)
2. **HTTPS**: Always use HTTPS in production
3. **Token Expiration**: Configure token expiration in Laravel Sanctum
4. **CORS**: Configure CORS properly in Laravel
5. **Rate Limiting**: Implement rate limiting on login endpoints
6. **CSRF Protection**: NextAuth handles CSRF automatically

## Laravel CORS Configuration

Update `config/cors.php`:

```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:3000')],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'supports_credentials' => true,
```

## Next Steps

1. Run migrations: `php artisan migrate`
2. Seed roles: `php artisan db:seed --class=RolesAndPermissionsSeeder`
3. Test authentication flow
4. Implement role-based UI components
5. Add permission checks to API routes

