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
      console.log(product, "Button + INC");

      let { id } = product;

      const csrfToken = await getCsrfToken();

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
      let result = await res.json();
      dispatch(fetchCart());
      //dispatch(addToCart(product));
      return result;
    } catch (err) {
      console.log(err.message);

      return rejectWithValue(err.message);
    }
  }
);

/// add to cart with button

export const addToCartDrupalQtyIncrase = createAsyncThunk(
  "cart/addToCartDrupalQtyIncrase",
  async (product, { dispatch, rejectWithValue }) => {
    try {
      const { id, quantity } = product;

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
      let result = await res.json();
      dispatch(fetchCart());
      return result;
    } catch (err) {
      console.log(err.message);

      return rejectWithValue(err.message);
    }
  }
);

// Remove from cart
export const removeFromCartDrupal = createAsyncThunk(
  "cart/removeFromCartDrupal",
  async ({ updatedProduct }, { dispatch, rejectWithValue }) => {
    // /let { updatedProduct: product } = updatedProduct;
    // const { product } = updatedProduct;
    // console.log(product);

    const cartUuid = updatedProduct.orderId;
    const orderItemUuid = updatedProduct.order_item_id;

    try {
      if (!cartUuid) throw new Error("No cart UUID provided");
      const csrf = await getCsrfToken();
      // console.log(csrf);

      const res = await fetch(`/cart/${cartUuid}/items/${orderItemUuid}`, {
        method: "DELETE",
        credentials: "include",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-Token": csrf,
        },
      });
      if (!res.ok) {
        const txt = await res.text();
        throw new Error(txt || "Failed to remove item");
      }
      // Refresh cart from server
      // dispatch(removeFromCart(updatedProduct));
      dispatch(fetchCart());
      // / console.log(orderItemUuid);

      return { orderItemUuid };
    } catch (err) {
      return rejectWithValue(err.message);
    }
  }
);

export const removeFromCartDrupalByOne = createAsyncThunk(
  "cart/removeFromCartDrupalByOne",
  async (product, { dispatch, rejectWithValue }) => {
    console.log(product, "BY --");
    try {
      const { id, quantity } = product;

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
            quantity: -1,
          },
        ]),
      });
      if (!res.ok) throw new Error("Failed to add to cart");
      let result = await res.json();
      dispatch(fetchCart());
      return result;
    } catch (err) {
      console.log(err.message);

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
      console.log(item, "AddToCart");

      state.cartItems.push({ ...item, quantity: 1 });
      console.log(JSON.parse(JSON.stringify(state.cartItems)));
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
      console.log(item.id);
      console.log(JSON.parse(JSON.stringify(state.cartItems)));

      state.cartItems = state.cartItems.map((cartItem) => {
        return cartItem.id === item.id
          ? { ...cartItem, quantity: cartItem.quantity + qty }
          : cartItem;
      });
      state.cartItems = state.cartItems.filter(
        (cartItem) => cartItem.quantity >= 1
      );
      console.log(JSON.parse(JSON.stringify(state.cartItems)));
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
      console.log(items);

      items.forEach((item) => {
        state.cartItems.push({
          ...item.product,
          quantity: item.quantity,
          uuid: item.uuid,
          order_id: item.order_id,
          order_item_id: item.order_item_id,
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
        // console.log("RR");
        // console.log(action.payload[0]);
        // console.log("cartItems");

        // console.log(JSON.parse(JSON.stringify(state.cartItems)));

        const simplifiedCart = action.payload[0].order_items.map((item) => ({
          id: String(item.purchased_entity?.product_id),
          uuid: item.uuid,
          price: item.purchased_entity.price.number,
          quantity: item.quantity,
          order_id: item.order_id,
          order_item_id: item.order_item_id,
          order_id: item.order_id,
        }));

        // console.log(simplifiedCart, "simplifiedCart");

        state.cartItems = simplifiedCart;
        // console.log(JSON.parse(JSON.stringify(state.cartItems)), "After");

        state.prevItems = simplifiedCart;
        // console.log("Test");
        // console.log(state.prevItems);

        //  console.log(state.prevItems);
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
      })
      .addCase(addToCartDrupalQtyIncrase.pending, (state) => {
        state.addStatus = "loading";
      })
      .addCase(addToCartDrupalQtyIncrase.fulfilled, (state) => {
        state.addStatus = "succeeded";
        state.error = null;
      })
      .addCase(addToCartDrupalQtyIncrase.rejected, (state, action) => {
        state.addStatus = "failed";
        state.error = action.payload;
      })
      .addCase(removeFromCartDrupal.pending, (state) => {
        state.status = "loading";
      })
      .addCase(removeFromCartDrupal.fulfilled, (state, action) => {
        state.status = "succeeded";
      })
      .addCase(removeFromCartDrupal.rejected, (state, action) => {
        state.status = "failed";
        state.error = action.payload;
      })
      .addCase(removeFromCartDrupalByOne.pending, (state, action) => {
        state.status = "loading";
      })
      .addCase(removeFromCartDrupalByOne.rejected, (state, action) => {
        state.status = "failed";
        state.error = action.payload;
      })
      .addCase(removeFromCartDrupalByOne.fulfilled, (state, action) => {
        state.status = "succeeded";
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
