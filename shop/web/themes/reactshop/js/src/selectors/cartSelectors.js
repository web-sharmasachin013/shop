import { createSelector } from "@reduxjs/toolkit";

// Basic selectors
const selectCartItems = (state) => state.cart;
const selectAllProducts = (state) => state.products;

export const selectEnrichedCartItems = createSelector(
  [selectCartItems, selectAllProducts],
  (cartItems, products) => {
    console.log("🛒 cartItems from state:", cartItems.prevItems);
    console.log("📦 products from state:", products.products);
    return cartItems.prevItems.map((item) => {
      const product = products.products.find(
        (p) => String(p.id) === String(item.product_id)
      );
      return {
        ...item,
        product: product || null,
      };
    });
  }
);
