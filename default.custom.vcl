/**
 * >jetrails_
 *
 * This file should be included within the default VCL file. It exists to help
 * customize the default VCL without having to modify the file itself. This way,
 * the default VCL can be updated without having to port over any customizations
 * that have been made. This is achievable by creating custom subroutines and
 * strategically executing them in the default VCL, effectively creating a
 * hooking system. The available hooks can be found below in the form of
 * subroutines. Their names end in either '_start' or '_end', to find when these
 * subroutines are executed, refer to the default VCL file.
 *
 * To keep things tidy, it is recommended that you include a description of the
 * issue that the given code snippet solves as well as any helpful references
 * that lead you to that solution.
 *
 * Please Note: Native Varnish subroutines such as 'vcl_synth' can also be
 * defined here since they are not currently being used by Magento. These
 * subroutines will be used in the default VCL, but it is important to make sure
 * that said subroutine is not defined twice.
 */

sub custom_recv_start {}
sub custom_recv_end {}
sub custom_hash_start {}
sub custom_hash_end {}
sub custom_process_graphql_headers_start {}
sub custom_process_graphql_headers_end {}
sub custom_backend_response_start {}
sub custom_backend_response_end {}
sub custom_deliver_start {}
sub custom_deliver_end {}
sub custom_hit_start {}
sub custom_hit_end {}
