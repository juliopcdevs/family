export interface User {
  id: string;
  name: string;
  email: string;
  email_verified_at: string | null;
  family_id: string | null;
  family?: Family;
  created_at: string;
  updated_at: string;
}

export interface Family {
  id: string;
  name: string;
  code: string;
  created_by: string;
  members?: User[];
  created_at: string;
  updated_at: string;
}

export interface ShoppingItem {
  id: string;
  name: string;
  slug: string;
  image_url: string | null;
}

export interface ShoppingListItem {
  id: string;
  family_id: string;
  item_name: string;
  item_slug: string | null;
  is_predefined: boolean;
  image_url: string | null;
  is_in_cart: boolean;
  usage_count: number;
  last_used_at: string;
  added_by: string;
}

export interface CalendarEvent {
  id: string;
  family_id: string;
  title: string;
  date: string;
  time: string | null;
  created_by: string;
  created_at: string;
}

export interface Task {
  id: string;
  family_id: string;
  title: string;
  is_completed: boolean;
  completed_at: string | null;
  created_by: string;
  creator_name: string;
  created_at: string;
}

export interface Birthday {
  id: string;
  family_id: string;
  person_name: string;
  birth_date: string;
  next_birthday: string;
  age_on_next_birthday: number;
  days_until_birthday: number;
  created_by: string;
}

export interface DashboardData {
  shopping: ShoppingListItem[];
  events: CalendarEvent[];
  tasks: Task[];
  birthdays: Birthday[];
}
