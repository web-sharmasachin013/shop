import React from "react";
import { useDispatch, useSelector } from "react-redux";
import { addToCart, removeFromCart } from "../../features/cart/cartSlice";
import { addToCartDrupal } from "../../features/cart/cartSlice";
import { useNavigate } from "react-router-dom";

function ProductButton(props) {
  const dispatch = useDispatch();
  const nav = useNavigate();
  const user = useSelector((state) => state.auth.user);
  const { cartItems } = useSelector((state) => state.cart);
  const handleAddClick = () => {
    const { product } = props;
    const userId = window?.drupalSettings?.user?.uid;
    if (!userId || userId === 0) {
      window.location.href = "/user/login"; // Drupal default login path
      return;
    }

    dispatch(addToCartDrupal({ product }));
  };

  const getUuidByProductId = (productId) => {
    const cartItem = cartItems.find((item) => item.id === productId);
    return cartItem ? cartItem.uuid : null;
  };
  const handleRemoveClick = () => {
    const uuid = getUuidByProductId(props.product.id);

    dispatch(removeFromCart(props.product));
  };
  const isPresentInCart = Boolean(
    cartItems.find((item) => item.id === props.product.id)
  );
  if (isPresentInCart) {
    return (
      <button
        onClick={handleRemoveClick}
        className="btn-fancy btn btn-outline-success d-block w-100"
      >
        Remove From Cart
      </button>
    );
  } else {
    return (
      <button
        onClick={handleAddClick}
        className="btn-fancy btn btn-outline-success d-block w-100"
      >
        Add To Cart
      </button>
    );
  }
}

export default ProductButton;
