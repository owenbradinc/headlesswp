# HeadlessWP SDK

A TypeScript SDK for interacting with WordPress REST API in a headless setup.

## Installation

```bash
npm install headlesswp
# or
yarn add headlesswp
```

## Configuration

First, you'll need to configure the SDK with your WordPress site's base URL and API key:

```typescript
import { HeadlessWP } from 'headlesswp';

const config = {
  baseUrl: 'https://your-wordpress-site.com', // Your WordPress site URL
  apiKey: 'your-api-key' // Your WordPress API key
};

const wp = new HeadlessWP(config);
```

## Usage

### Posts

```typescript
// List posts with pagination
const posts = await wp.posts.list({
  page: 1,
  per_page: 10,
  search: 'keyword',
  categories: [1, 2],
  tags: [3, 4]
});

// Get a single post
const post = await wp.posts.getById(123);

// Create a new post
const newPost = await wp.posts.create({
  title: 'New Post',
  content: 'Post content',
  status: 'publish'
});

// Update a post
const updatedPost = await wp.posts.update(123, {
  title: 'Updated Title'
});

// Delete a post
await wp.posts.deleteById(123);
```

### Pages

```typescript
// List pages with pagination
const pages = await wp.pages.list({
  page: 1,
  per_page: 10,
  search: 'keyword'
});

// Get a single page
const page = await wp.pages.getById(123);

// Create a new page
const newPage = await wp.pages.create({
  title: 'New Page',
  content: 'Page content',
  status: 'publish'
});

// Update a page
const updatedPage = await wp.pages.update(123, {
  title: 'Updated Title'
});

// Delete a page
await wp.pages.deleteById(123);
```

## List Options

The `list` method accepts the following options:

```typescript
interface ListOptions {
  page?: number;           // Page number
  per_page?: number;       // Number of items per page
  search?: string;         // Search term
  after?: string;          // Date after which to retrieve posts
  before?: string;         // Date before which to retrieve posts
  author?: number;         // Author ID
  author_exclude?: number[]; // Author IDs to exclude
  exclude?: number[];      // Post IDs to exclude
  include?: number[];      // Post IDs to include
  offset?: number;         // Number of items to offset
  order?: 'asc' | 'desc';  // Sort order
  orderby?: 'date' | 'id' | 'include' | 'relevance' | 'slug' | 'title'; // Sort field
  slug?: string;           // Post slug
  status?: string;         // Post status
  categories?: number[];   // Category IDs
  categories_exclude?: number[]; // Category IDs to exclude
  tags?: number[];         // Tag IDs
  tags_exclude?: number[]; // Tag IDs to exclude
  sticky?: boolean;        // Whether to include sticky posts
}
```

## Response Format

List responses include pagination information:

```typescript
interface ListResponse<T> {
  data: T[];           // Array of items
  total: number;       // Total number of items
  totalPages: number;  // Total number of pages
  currentPage: number; // Current page number
}
```

## Error Handling

The SDK throws errors when API requests fail. You can catch these errors and handle them appropriately:

```typescript
try {
  const posts = await wp.posts.list();
} catch (error) {
  if (error instanceof Error) {
    console.error('API Error:', error.message);
  }
}
```

## Types

The SDK includes TypeScript types for all WordPress entities and responses. You can import these types for use in your application:

```typescript
import { Post, Page, ListOptions, ListResponse } from 'headlesswp';
```

## License

MIT
