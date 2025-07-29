import React from "react";
import Swal from "sweetalert2";
import { useNavigate } from "react-router-dom";
function CartBuyButton() {
  const nav = useNavigate();
  const buy = async () => {
    const result = await Swal.fire({
      title: "Do you want to palce the order ?",
      showDenyButton: true,
      confirmButtonText: "Place order",
      denyButtonText: "Don't Place",
    });
    if (result.isConfirmed) {
      await Swal.fire({
        title: "Done",
        text: "Order placed successfully",
        icon: "success",
      });
      nav("/");
      window.location.reload();
    } else if (result.isDenied) {
      await Swal.fire({
        title: "Order not placed",
        text: "",
        icon: "info",
      });
    }
  };
  return (
    <button
      className="btn btn-success d-block w-100 fw-bold mt-3"
      onClick={buy}
    >
      Buy Now
    </button>
  );
}

export default CartBuyButton;
