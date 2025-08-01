import { createSelector } from "@reduxjs/toolkit";

// Basic selectors
const selectCartItems = (state) => state.cart;
const selectAllProducts = (state) => state.products;

export const selectEnrichedCartItems = createSelector(
  [selectCartItems, selectAllProducts],
  (cartItems, products) => {
    return cartItems.prevItems.map((item) => {
      const product = products.products.find(
        (p) => String(p.id) === String(item.product_id)
      );

      const quantity = parseFloat(item.quantity || 0);
      const price = parseFloat(product?.price || 0);
      const subtotal = quantity * price;

      return {
        ...item,
        product: product || null,
        subtotal,
      };
    });
  }
);
