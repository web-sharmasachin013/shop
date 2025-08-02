import { createSlice, createAsyncThunk } from "@reduxjs/toolkit";
import data from "../../data";
const initialState = {
  cartItems: [],
  prevItems: [],
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
      // console.log(data);

      return data;
    } catch (error) {
      return rejectWithValue(error.message);
    }
  }
);

// Add to  cart data thunk
export const addToCartDrupal = createAsyncThunk(
  "cart/addToCartDrupal",
  async ({ product }, { dispatch, rejectWithValue }) => {
    try {
      let { id } = product;

      const csrfToken = await getCsrfToken();
      // console.log(csrfToken);

      const res = await fetch("/cart/add?_format=json", {
        method: "POST",
        credentials: "include",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-Token": csrfToken,
        },
        body: JSON.stringify([
          {
            purchased_entity_type: "commerce_product_variation",
            purchased_entity_id: id,
            quantity: 1,
          },
        ]),
      });
      if (!res.ok) throw new Error("Failed to add to cart");
      dispatch(addToCart(product));
      return await res.json();
    } catch (err) {
      return rejectWithValue(err.message);
    }
  }
);

// src/utils/getCsrfToken.js
export async function getCsrfToken() {
  const res = await fetch("/session/token", {
    credentials: "include",
  });

  if (!res.ok) {
    throw new Error("Failed to fetch CSRF token");
  }

  return res.text();
}

export const cartSlice = createSlice({
  name: "products",
  initialState,
  reducers: {
    addToCart: (state, action) => {
      let { payload: item } = action;
      // console.log(item);

      // console.log(JSON.parse(JSON.stringify(item))); // Clean output
      state.cartItems.push({ ...item, quantity: 1 });
      //  console.log(JSON.parse(JSON.stringify(item))); // Clean output
    },
    removeFromCart: (state, action) => {
      let { payload: item } = action;
      console.log(item);
      console.log(JSON.parse(JSON.stringify(state.cartItems)));

      let index = state.cartItems.findIndex(
        (cartItem) => cartItem.id === item.id
      );
      state.cartItems.splice(index, 1);
    },
    setQuantity: (state, action) => {
      let { item, qty } = action.payload;
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
    viewCartItems: (state, action) => {
      let { payload: items } = action;
      console.log("viewCartItems");
      console.log(items);

      items.forEach((item) => {
        state.cartItems.push({
          ...item.product,
          quantity: item.quantity,
          uuid: item.uuid,
          order_item: item.order_item,
        });
      });
    },
  },

  extraReducers: (builder) => {
    builder
      .addCase(fetchCart.pending, (state) => {
        state.status = "loading";
      })
      .addCase(fetchCart.fulfilled, (state, action) => {
        state.status = "succeeded";

        const simplifiedCart = action.payload[0].order_items.map((item) => ({
          product_id: item.purchased_entity?.product_id,
          uuid: item.uuid,
          price: item.purchased_entity.price.number,
          quantity: item.quantity,
          order_id: item.order_id,
          order_item_id: item.order_item_id,
        }));
        state.prevItems = simplifiedCart;
      })
      .addCase(fetchCart.rejected, (state, action) => {
        state.status = "failed";
        state.error = action.payload;
      })
      .addCase(addToCartDrupal.pending, (state) => {
        state.addStatus = "loading";
      })
      .addCase(addToCartDrupal.fulfilled, (state) => {
        state.addStatus = "succeeded";
        state.error = null;
      })
      .addCase(addToCartDrupal.rejected, (state, action) => {
        state.addStatus = "failed";
        state.error = action.payload;
      });
  },
});
export const {
  addToCart,
  removeFromCart,
  setQuantity,
  setCartNumbers,
  viewCartItems,
} = cartSlice.actions;
export default cartSlice.reducer;
