import { createSlice, createAsyncThunk } from "@reduxjs/toolkit";
import data from "../../data";
const initialState = {
  cartItems: [],
  cartNumbers: { subtotal: 0, shipping: 0, tax: 0, total: 0 },
};

// Fetch cart data thunk
export const fetchCart = createAsyncThunk(
  "cart/fetchCart",
  async (_, { rejectWithValue }) => {
    try {
      const response = await fetch("/cart?_format=json", {
        credentials: "include", // important for authenticated session
      });

      if (!response.ok) throw new Error("Failed to fetch cart");

      const data = await response.json();
      console.log("Cart DAta");
      console.log(data);

      return data;
    } catch (error) {
      return rejectWithValue(error.message);
    }
  }
);

export const cartSlice = createSlice({
  name: "products",
  initialState,
  reducers: {
    addToCart: (state, action) => {
      let { payload: item } = action;
      state.cartItems.push({ ...item, quantity: 1 });
    },
    removeFromCart: (state, action) => {
      let { payload: item } = action;
      let index = state.cartItems.findIndex(
        (cartItem) => cartItem.id === item.id
      );
      state.cartItems.splice(index, 1);
    },
    setQuantity: (state, action) => {
      let { item, qty } = action.payload;
      console.log("setQty called", item, qty);
      state.cartItems = state.cartItems.map((cartItem) => {
        return cartItem.id === item.id
          ? { ...cartItem, quantity: cartItem.quantity + qty }
          : cartItem;
      });
      state.cartItems = state.cartItems.filter(
        (cartItem) => cartItem.quantity >= 1
      );
    },
    setCartNumbers: (state) => {
      let subtotal = 0,
        shipping = 0,
        tax = 0,
        total = 0;
      for (let item of state.cartItems) {
        subtotal += item.quantity * item.price;
        shipping += item.quantity * 40;
      }
      tax = (subtotal * 18) / 100;
      total = subtotal + shipping + tax;
      state.cartNumbers = { subtotal, shipping, tax, total };
    },
  },

  extraReducers: (builder) => {
    builder
      .addCase(fetchCart.pending, (state) => {
        state.status = "loading";
      })
      .addCase(fetchCart.fulfilled, (state, action) => {
        state.status = "succeeded";
        state.cartItems = action.payload;
      })
      .addCase(fetchCart.rejected, (state, action) => {
        state.status = "failed";
        state.error = action.payload;
      });
  },
});
export const { addToCart, removeFromCart, setQuantity, setCartNumbers } =
  cartSlice.actions;
export default cartSlice.reducer;
